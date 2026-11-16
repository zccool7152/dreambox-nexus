<?php
declare(strict_types=1);

namespace Nexus\Mail;

use InvalidArgumentException;
use Nexus\Core\Env;
use Nexus\Crypto\Crypto;
use Nexus\Database\Database;
use RuntimeException;

/**
 * Hybrid Webmail Repository (MongoDB + SQL + Atomic JSON Store Fallback)
 *
 * Manages per-user mailbox claims (@MAIL_DOMAIN / nexus.local fallback) and
 * AES-256-GCM encrypted message storage across folders (inbox, starred, sent, drafts, trash).
 */
class MailRepository
{
    private ?Database $db;
    private string $storeDir;
    private static bool $mongoIndexesEnsured = false;

    public const VALID_FOLDERS = ['inbox', 'starred', 'sent', 'drafts', 'trash'];

    public function __construct(?Database $db = null, ?string $storeDir = null)
    {
        $this->db = $db;
        $configuredDir = rtrim($storeDir ?? (dirname(__DIR__, 2) . '/storage/app/private/mail'), '/');
        $this->storeDir = $this->resolveStoreDir($configuredDir);
    }

    /**
     * Resolve a strictly 0700 writable mail store directory for the current process UID.
     */
    private function resolveStoreDir(string $configuredDir): string
    {
        if ($this->isOwnedWritableDir($configuredDir)) {
            return $configuredDir;
        }

        return $this->ensureFallbackStoreDir();
    }

    private function ensureFallbackStoreDir(): string
    {
        $uid = function_exists('posix_geteuid') ? (string)posix_geteuid() : 'default';
        $fallbackDir = rtrim(sys_get_temp_dir(), '/') . '/nexus_mail_' . $uid;
        if (!is_dir($fallbackDir)) {
            @mkdir($fallbackDir, 0700, true);
        }
        @chmod($fallbackDir, 0700);
        return $fallbackDir;
    }

    private function isOwnedWritableDir(string $dir): bool
    {
        if (!is_dir($dir)) {
            @mkdir($dir, 0700, true);
        }
        if (!is_dir($dir) || is_link($dir)) {
            return false;
        }
        if (function_exists('posix_geteuid')) {
            $owner = @fileowner($dir);
            if ($owner !== false && $owner !== posix_geteuid()) {
                return false;
            }
        }
        @chmod($dir, 0700);

        $probe = $dir . '/.nx_write_probe_' . getmypid();
        $ok = @file_put_contents($probe, '1', LOCK_EX);
        if ($ok === false) {
            return false;
        }
        @unlink($probe);

        // If messages.json already exists in this dir, ensure the current UID can also write it
        $existingMessages = $dir . '/messages.json';
        if (is_file($existingMessages)) {
            $h = @fopen($existingMessages, 'r+');
            if ($h === false) {
                return false;
            }
            @fclose($h);
        }

        return true;
    }

    /**
     * Resolve configured email domain from MAIL_DOMAIN in .env, defaulting cleanly to nexus.local.
     */
    public static function getMailDomain(): string
    {
        $raw = strtolower(trim((string)Env::get('MAIL_DOMAIN', ''), " \t\n\r\0\x0B@"));
        if ($raw === '' || !preg_match('/^[a-z0-9](?:[a-z0-9.-]*[a-z0-9])?$/', $raw)) {
            return 'nexus.local';
        }
        return $raw;
    }

    public static function normalizeLocalPart(string $input): string
    {
        $raw = strtolower(trim($input));
        if (str_contains($raw, '@')) {
            $raw = (string)strstr($raw, '@', true);
        }
        return trim($raw);
    }

    public static function isValidLocalPart(string $localPart): bool
    {
        return (bool)preg_match('/^[a-z0-9](?:[a-z0-9._-]{0,30}[a-z0-9])?$/', $localPart);
    }

    public function getMailboxForUser(string $userId): ?array
    {
        if ($this->isMongo()) {
            $doc = $this->db->mongoFindOne('mailboxes', ['user_id' => $userId]);
            if ($doc) {
                return $this->normalizeMailbox($doc);
            }
        } elseif ($this->isSqlAlive()) {
            try {
                $row = $this->db->fetch('SELECT * FROM mailboxes WHERE user_id = ? LIMIT 1', [$userId]);
                if ($row) {
                    return $this->normalizeMailbox($row);
                }
            } catch (\Throwable) {}
        }

        foreach ($this->readJsonStore('mailboxes.json') as $mb) {
            if (($mb['user_id'] ?? '') === $userId) {
                return $this->normalizeMailbox($mb);
            }
        }

        return null;
    }

    public function getMailboxByAddress(string $address): ?array
    {
        $address = strtolower(trim($address));
        if ($address === '') {
            return null;
        }

        if ($this->isMongo()) {
            $doc = $this->db->mongoFindOne('mailboxes', ['address' => $address]);
            if ($doc) {
                return $this->normalizeMailbox($doc);
            }
        } elseif ($this->isSqlAlive()) {
            try {
                $row = $this->db->fetch('SELECT * FROM mailboxes WHERE address = ? LIMIT 1', [$address]);
                if ($row) {
                    return $this->normalizeMailbox($row);
                }
            } catch (\Throwable) {}
        }

        foreach ($this->readJsonStore('mailboxes.json') as $mb) {
            if (strtolower((string)($mb['address'] ?? '')) === $address) {
                return $this->normalizeMailbox($mb);
            }
        }

        return null;
    }

    /**
     * Claim a unique mailbox address (<localPart>@<domain>) on first Webmail visit.
     */
    public function claimMailbox(string $userId, string $localPart, ?string $domain = null): array
    {
        $cleanLocal = self::normalizeLocalPart($localPart);
        if (!self::isValidLocalPart($cleanLocal)) {
            throw new InvalidArgumentException(
                'Email handle must be 2–32 characters using lowercase letters, numbers, dots, underscores, or hyphens.'
            );
        }

        $targetDomain = $domain !== null && trim($domain) !== ''
            ? strtolower(trim($domain, " \t\n\r\0\x0B@"))
            : self::getMailDomain();
        $address = $cleanLocal . '@' . $targetDomain;

        $claimedRecord = null;
        $isNewClaim = false;

        $this->withStoreLock('mailboxes.json', function (array $mailboxes) use ($userId, $cleanLocal, $targetDomain, $address, &$claimedRecord, &$isNewClaim): array {
            foreach ($mailboxes as $mb) {
                $mbArr = self::toArrayRecursive($mb);
                if (($mbArr['user_id'] ?? '') === $userId) {
                    $claimedRecord = $mbArr;
                    return $mailboxes;
                }
                if (strtolower((string)($mbArr['address'] ?? '')) === $address) {
                    throw new RuntimeException("The address '{$address}' is already claimed by another user.");
                }
            }

            $existingOwn = $this->getMailboxForUser($userId);
            if ($existingOwn !== null) {
                $claimedRecord = $existingOwn;
                return $mailboxes;
            }

            if ($this->getMailboxByAddress($address) !== null) {
                throw new RuntimeException("The address '{$address}' is already claimed by another user.");
            }

            $record = [
                'id' => 'mb_' . bin2hex(random_bytes(8)),
                'user_id' => $userId,
                'address' => $address,
                'local_part' => $cleanLocal,
                'domain' => $targetDomain,
                'created_at' => gmdate('Y-m-d H:i:s'),
            ];

            if ($this->isMongo()) {
                $this->ensureMongoMailboxIndexes();
                try {
                    $this->db->mongoInsert('mailboxes', $record);
                } catch (\Throwable $e) {
                    if (str_contains($e->getMessage(), 'E11000') || str_contains(strtolower($e->getMessage()), 'duplicate')) {
                        throw new RuntimeException("The address '{$address}' is already claimed by another user.", 0, $e);
                    }
                    throw $e;
                }
            } elseif ($this->isSqlAlive()) {
                try {
                    $this->db->execute(
                        'INSERT INTO mailboxes (id, user_id, address, local_part, domain, created_at) VALUES (?, ?, ?, ?, ?, ?)',
                        [$record['id'], $record['user_id'], $record['address'], $record['local_part'], $record['domain'], $record['created_at']]
                    );
                } catch (\Throwable) {}
            }

            $mailboxes[] = $record;
            $claimedRecord = $record;
            $isNewClaim = true;
            return $mailboxes;
        });

        if ($isNewClaim && $claimedRecord !== null) {
            $this->seedInitialMessages($userId, $address, $targetDomain);
        }

        return $this->normalizeMailbox($claimedRecord ?? []);
    }

    /**
     * List messages for a user folder with optional keyword search, newest first.
     */
    public function getMessages(string $userId, string $folder = 'inbox', string $search = ''): array
    {
        $folder = in_array($folder, self::VALID_FOLDERS, true) ? $folder : 'inbox';
        $all = $this->loadAllUserMessages($userId);
        $needle = strtolower(trim($search));

        $filtered = array_filter($all, function (array $msg) use ($folder, $needle): bool {
            $msgFolder = (string)($msg['folder'] ?? 'inbox');
            if ($folder === 'starred') {
                if (empty($msg['is_starred']) || $msgFolder === 'trash') {
                    return false;
                }
            } elseif ($msgFolder !== $folder) {
                return false;
            }

            if ($needle !== '') {
                $haystack = strtolower(
                    ($msg['subject'] ?? '') . ' ' .
                    ($msg['from_name'] ?? '') . ' ' .
                    ($msg['from_address'] ?? '') . ' ' .
                    ($msg['to_address'] ?? '') . ' ' .
                    ($msg['body_text'] ?? '')
                );
                if (!str_contains($haystack, $needle)) {
                    return false;
                }
            }

            return true;
        });

        $list = array_values($filtered);
        usort($list, fn(array $a, array $b) => strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? '')));

        return $list;
    }

    public function getFolderCounts(string $userId): array
    {
        $counts = [
            'inbox_unread' => 0,
            'inbox' => 0,
            'starred' => 0,
            'sent' => 0,
            'drafts' => 0,
            'trash' => 0,
        ];

        foreach ($this->loadAllUserMessages($userId) as $msg) {
            $folder = (string)($msg['folder'] ?? 'inbox');
            if (isset($counts[$folder])) {
                $counts[$folder]++;
            }
            if ($folder === 'inbox' && empty($msg['is_read'])) {
                $counts['inbox_unread']++;
            }
            if (!empty($msg['is_starred']) && $folder !== 'trash') {
                $counts['starred']++;
            }
        }

        return $counts;
    }

    public function getMessage(string $userId, string $messageId): ?array
    {
        foreach ($this->loadAllUserMessages($userId) as $msg) {
            if (($msg['id'] ?? '') === $messageId) {
                return $msg;
            }
        }
        return null;
    }

    public function saveMessage(array $message): array
    {
        $scanner = new MailScanner();
        $scan = $scanner->scan(
            (string)($message['subject'] ?? ''),
            (string)($message['body_text'] ?? ''),
            (string)($message['body_html'] ?? ''),
            (array)($message['attachments'] ?? [])
        );

        $messageId = (string)($message['id'] ?? ('msg_' . bin2hex(random_bytes(8))));
        $ownerUserId = (string)($message['owner_user_id'] ?? '');
        $aad = $messageId . '|' . $ownerUserId;

        $plainSubject = (string)($message['subject'] ?? '(No Subject)');
        $plainBodyText = (string)($message['body_text'] ?? '');
        $plainBodyHtml = (string)$scan['sanitized_html'];

        $record = [
            'id' => $messageId,
            'owner_user_id' => $ownerUserId,
            'mailbox_address' => strtolower(trim((string)($message['mailbox_address'] ?? ''))),
            'folder' => (string)($message['folder'] ?? 'inbox'),
            'from_name' => (string)($message['from_name'] ?? ''),
            'from_address' => strtolower(trim((string)($message['from_address'] ?? ''))),
            'to_address' => strtolower(trim((string)($message['to_address'] ?? ''))),
            'subject' => $this->encryptField($plainSubject, $aad),
            'body_text' => $this->encryptField($plainBodyText, $aad),
            'body_html' => $this->encryptField($plainBodyHtml, $aad),
            'attachments' => MailScanner::normalizeAttachments($message['attachments'] ?? []),
            'is_read' => !empty($message['is_read']),
            'is_starred' => !empty($message['is_starred']),
            'scan_status' => $scan['status'],
            'scan_threats' => $scan['threats'],
            'spam_score' => (float)($scan['spam_score'] ?? 0.0),
            'spam_action' => (string)($scan['spam_action'] ?? 'no action'),
            'scanned_at' => $scan['scanned_at'],
            'created_at' => (string)($message['created_at'] ?? gmdate('Y-m-d H:i:s')),
        ];

        $this->withStoreLock('messages.json', function (array $messages) use ($record): array {
            $replaced = false;
            foreach ($messages as $i => $existing) {
                if (($existing['id'] ?? '') === $record['id']) {
                    $messages[$i] = $record;
                    $replaced = true;
                    break;
                }
            }
            if (!$replaced) {
                $messages[] = $record;
            }
            return $messages;
        });

        if ($this->isMongo()) {
            $existing = $this->db->mongoFindOne('mail_messages', ['id' => $record['id'], 'owner_user_id' => $record['owner_user_id']]);
            if ($existing !== null) {
                $this->db->mongoUpdate('mail_messages', ['id' => $record['id'], 'owner_user_id' => $record['owner_user_id']], ['$set' => $record]);
            } else {
                $this->db->mongoInsert('mail_messages', $record);
            }
        } elseif ($this->isSqlAlive()) {
            try {
                $this->db->execute(
                    'INSERT INTO mail_messages (id, owner_user_id, mailbox_address, folder, from_name, from_address, to_address, subject, body_text, body_html, attachments, is_read, is_starred, scan_status, scan_threats, scanned_at, created_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [
                        $record['id'],
                        $record['owner_user_id'],
                        $record['mailbox_address'],
                        $record['folder'],
                        $record['from_name'],
                        $record['from_address'],
                        $record['to_address'],
                        $record['subject'],
                        $record['body_text'],
                        $record['body_html'],
                        json_encode($record['attachments']),
                        $record['is_read'] ? 1 : 0,
                        $record['is_starred'] ? 1 : 0,
                        $record['scan_status'],
                        json_encode($record['scan_threats']),
                        $record['scanned_at'],
                        $record['created_at'],
                    ]
                );
            } catch (\Throwable) {}
        }

        return $this->normalizeMessage($record);
    }

    public function updateMessage(string $userId, string $messageId, array $fields): ?array
    {
        $updated = null;
        $aad = $messageId . '|' . $userId;
        $persistedFields = [];
        foreach (['is_read', 'is_starred', 'folder', 'subject', 'to_address', 'body_text', 'body_html'] as $k) {
            if (array_key_exists($k, $fields)) {
                if (in_array($k, ['subject', 'body_text', 'body_html'], true)) {
                    $persistedFields[$k] = $this->encryptField((string)$fields[$k], $aad);
                } else {
                    $persistedFields[$k] = $fields[$k];
                }
            }
        }

        $this->withStoreLock('messages.json', function (array $messages) use ($userId, $messageId, $persistedFields, &$updated): array {
            foreach ($messages as $i => $msg) {
                if (($msg['id'] ?? '') === $messageId && ($msg['owner_user_id'] ?? '') === $userId) {
                    foreach ($persistedFields as $k => $v) {
                        $messages[$i][$k] = $v;
                    }
                    $updated = $this->normalizeMessage($messages[$i]);
                    break;
                }
            }
            return $messages;
        });

        if ($this->isMongo() && !empty($persistedFields)) {
            $mod = $this->db->mongoUpdate('mail_messages', ['id' => $messageId, 'owner_user_id' => $userId], ['$set' => $persistedFields]);
            if ($updated === null && $mod >= 0) {
                $updated = $this->getMessage($userId, $messageId);
            }
        } elseif ($this->isSqlAlive() && !empty($persistedFields)) {
            try {
                $sets = [];
                $params = [];
                foreach ($persistedFields as $k => $v) {
                    $sets[] = "{$k} = ?";
                    $params[] = is_bool($v) ? ($v ? 1 : 0) : $v;
                }
                if (!empty($sets)) {
                    $params[] = $messageId;
                    $params[] = $userId;
                    $this->db->execute('UPDATE mail_messages SET ' . implode(', ', $sets) . ' WHERE id = ? AND owner_user_id = ?', $params);
                    if ($updated === null) {
                        $updated = $this->getMessage($userId, $messageId);
                    }
                }
            } catch (\Throwable) {}
        }

        return $updated;
    }

    public function deleteMessageForever(string $userId, string $messageId): bool
    {
        $deleted = false;

        $this->withStoreLock('messages.json', function (array $messages) use ($userId, $messageId, &$deleted): array {
            $next = [];
            foreach ($messages as $msg) {
                if (($msg['id'] ?? '') === $messageId && ($msg['owner_user_id'] ?? '') === $userId) {
                    $deleted = true;
                    continue;
                }
                $next[] = $msg;
            }
            return $next;
        });

        if ($this->isMongo()) {
            $mongoDeleted = $this->db->mongoDelete('mail_messages', ['id' => $messageId, 'owner_user_id' => $userId]);
            $deleted = $deleted || $mongoDeleted > 0;
        } elseif ($this->isSqlAlive()) {
            try {
                $sqlDeleted = $this->db->execute('DELETE FROM mail_messages WHERE id = ? AND owner_user_id = ?', [$messageId, $userId]);
                $deleted = $deleted || $sqlDeleted > 0;
            } catch (\Throwable) {}
        }

        return $deleted;
    }

    private function seedInitialMessages(string $userId, string $mailboxAddress, string $domain): void
    {
        $now = time();

        $seeds = [
            [
                'id' => 'msg_seed_' . bin2hex(random_bytes(6)),
                'owner_user_id' => $userId,
                'mailbox_address' => $mailboxAddress,
                'folder' => 'inbox',
                'from_name' => 'Dreambox Nexus Core',
                'from_address' => 'system@' . $domain,
                'to_address' => $mailboxAddress,
                'subject' => 'Welcome to Dreambox Nexus Webmail',
                'body_text' => "Your Nexus mailbox ({$mailboxAddress}) is active.\n\nAll incoming and outgoing mail is automatically scanned by the Nexus Antivirus Engine and rendered inside an ephemeral Reader Jail.",
                'body_html' => '<p>Welcome to <strong>Dreambox Nexus Webmail</strong>.</p>'
                    . '<p>Your dedicated mailbox address <code>' . htmlspecialchars($mailboxAddress, ENT_QUOTES, 'UTF-8') . '</code> has been provisioned on domain <strong>' . htmlspecialchars($domain, ENT_QUOTES, 'UTF-8') . '</strong>.</p>'
                    . '<ul><li>Every message is scanned by <strong>Nexus AV + Heuristic Engine</strong>.</li><li>Opening a message launches a temporary isolated <strong>Reader Jail</strong> that shreds itself immediately when you close the message.</li></ul>',
                'is_read' => false,
                'is_starred' => true,
                'created_at' => gmdate('Y-m-d H:i:s', $now - 60),
            ],
            [
                'id' => 'msg_seed_' . bin2hex(random_bytes(6)),
                'owner_user_id' => $userId,
                'mailbox_address' => $mailboxAddress,
                'folder' => 'inbox',
                'from_name' => 'Nexus SecOps Team',
                'from_address' => 'secops@' . $domain,
                'to_address' => $mailboxAddress,
                'subject' => 'Zero-Trust Ephemeral Reader Jail & AV Policy',
                'body_text' => 'Security Brief: Every email opened in Webmail runs inside an isolated iframe sandbox and a temporary jail container under storage/cache/jails/.',
                'body_html' => '<p><strong>SecOps Advisory:</strong> Zero-Trust containment is enabled on your mailbox.</p>'
                    . '<blockquote>When you open any message, scripts, external images, forms, and cookies are blocked inside a zero-privilege sandbox container that destroys itself upon closing.</blockquote>',
                'is_read' => true,
                'is_starred' => false,
                'created_at' => gmdate('Y-m-d H:i:s', $now - 180),
            ],
            [
                'id' => 'msg_seed_' . bin2hex(random_bytes(6)),
                'owner_user_id' => $userId,
                'mailbox_address' => $mailboxAddress,
                'folder' => 'inbox',
                'from_name' => 'External Threat Sandbox',
                'from_address' => 'threat-sim@external-sandbox.test',
                'to_address' => $mailboxAddress,
                'subject' => 'Invoice_Urgent_Macro.exe [AV Simulation]',
                'body_text' => 'Simulated malicious payload test containing an EICAR signature and blocked script tag. ' . MailScanner::EICAR_SIGNATURE,
                'body_html' => '<p><strong>Quarantine Simulation Notice:</strong> This test message demonstrates Nexus AV detection and Reader Jail neutralization.</p>'
                    . '<p>Detected test string (neutralized): <code>' . MailScanner::EICAR_SIGNATURE . '</code></p>'
                    . '<script>window.alert("XSS should never run");</script>',
                'attachments' => [
                    ['name' => 'Invoice_Urgent_Macro.exe', 'size' => 42080],
                ],
                'is_read' => false,
                'is_starred' => false,
                'created_at' => gmdate('Y-m-d H:i:s', $now - 300),
            ],
        ];

        foreach ($seeds as $seed) {
            $this->saveMessage($seed);
        }
    }

    private function loadAllUserMessages(string $userId): array
    {
        if ($this->isMongo()) {
            $docs = $this->db->mongoFind('mail_messages', ['owner_user_id' => $userId], ['sort' => ['created_at' => -1]]);
            if (!empty($docs)) {
                return array_map([$this, 'normalizeMessage'], $docs);
            }
        } elseif ($this->isSqlAlive()) {
            try {
                $rows = $this->db->fetchAll('SELECT * FROM mail_messages WHERE owner_user_id = ? ORDER BY created_at DESC', [$userId]);
                if (!empty($rows)) {
                    return array_map([$this, 'normalizeMessage'], $rows);
                }
            } catch (\Throwable) {}
        }

        $out = [];
        foreach ($this->readJsonStore('messages.json') as $msg) {
            if (($msg['owner_user_id'] ?? '') === $userId) {
                $out[] = $this->normalizeMessage($msg);
            }
        }
        return $out;
    }

    private function normalizeMailbox(array|object $mb): array
    {
        $mb = self::toArrayRecursive($mb);
        return [
            'id' => (string)($mb['id'] ?? $mb['_id'] ?? ''),
            'user_id' => (string)($mb['user_id'] ?? ''),
            'address' => strtolower((string)($mb['address'] ?? '')),
            'local_part' => strtolower((string)($mb['local_part'] ?? '')),
            'domain' => strtolower((string)($mb['domain'] ?? self::getMailDomain())),
            'created_at' => (string)($mb['created_at'] ?? ''),
        ];
    }

    private function normalizeMessage(array|object $m): array
    {
        $m = self::toArrayRecursive($m);
        $id = (string)($m['id'] ?? $m['_id'] ?? '');
        $ownerUserId = (string)($m['owner_user_id'] ?? '');
        $aad = $id . '|' . $ownerUserId;

        $attachments = $m['attachments'] ?? [];
        if (is_string($attachments)) {
            $attachments = json_decode($attachments, true) ?: [];
        }
        $attachments = MailScanner::normalizeAttachments($attachments);

        $threats = $m['scan_threats'] ?? [];
        if (is_string($threats)) {
            $threats = json_decode($threats, true) ?: [];
        }
        $threats = is_array($threats)
            ? array_values(array_map(static fn($t): string => is_scalar($t) ? (string)$t : json_encode($t), $threats))
            : [];

        return [
            'id' => $id,
            'owner_user_id' => $ownerUserId,
            'mailbox_address' => (string)($m['mailbox_address'] ?? ''),
            'folder' => (string)($m['folder'] ?? 'inbox'),
            'from_name' => (string)($m['from_name'] ?? ''),
            'from_address' => (string)($m['from_address'] ?? ''),
            'to_address' => (string)($m['to_address'] ?? ''),
            'subject' => $this->decryptField((string)($m['subject'] ?? '(No Subject)'), $aad),
            'body_text' => $this->decryptField((string)($m['body_text'] ?? ''), $aad),
            'body_html' => $this->decryptField((string)($m['body_html'] ?? ''), $aad),
            'attachments' => $attachments,
            'is_read' => !empty($m['is_read']),
            'is_starred' => !empty($m['is_starred']),
            'scan_status' => (string)($m['scan_status'] ?? 'clean'),
            'scan_threats' => $threats,
            'spam_score' => (float)($m['spam_score'] ?? 0.0),
            'spam_action' => (string)($m['spam_action'] ?? 'no action'),
            'scanned_at' => (string)($m['scanned_at'] ?? ''),
            'created_at' => (string)($m['created_at'] ?? ''),
        ];
    }

    private static function toArrayRecursive(mixed $value): mixed
    {
        if (is_object($value)) {
            $value = $value instanceof \Traversable
                ? iterator_to_array($value)
                : (array)$value;
        }
        if (!is_array($value)) {
            return $value;
        }
        foreach ($value as $k => $v) {
            $value[$k] = self::toArrayRecursive($v);
        }
        return $value;
    }

    private function getCrypto(): Crypto
    {
        $raw = (string)Env::get('APP_KEY', '');
        if (str_starts_with($raw, 'base64:')) {
            $decoded = base64_decode(substr($raw, 7), true);
            if ($decoded !== false && strlen($decoded) >= 32) {
                return new Crypto($raw);
            }
        }
        $derived = strlen($raw) >= 32
            ? $raw
            : hash('sha256', $raw !== '' ? $raw : 'dreambox_nexus_mail_dev_fallback_key', true);
        return new Crypto('base64:' . base64_encode($derived));
    }

    private function encryptField(string $plaintext, string $aad): string
    {
        return 'enc:v1:' . $this->getCrypto()->encrypt($plaintext, $aad);
    }

    private function decryptField(string $value, string $aad): string
    {
        if (!str_starts_with($value, 'enc:v1:')) {
            return $value;
        }
        try {
            return $this->getCrypto()->decrypt(substr($value, 7), $aad);
        } catch (\Throwable) {
            return '';
        }
    }

    private function ensureMongoMailboxIndexes(): void
    {
        if (self::$mongoIndexesEnsured || !$this->isMongo()) {
            return;
        }
        self::$mongoIndexesEnsured = true;
        try {
            $this->db->mongoCommand([
                'createIndexes' => 'mailboxes',
                'indexes' => [
                    ['key' => ['address' => 1], 'name' => 'uniq_mailbox_address', 'unique' => true],
                    ['key' => ['user_id' => 1], 'name' => 'uniq_mailbox_user_id', 'unique' => true],
                ],
            ]);
        } catch (\Throwable) {}
    }

    /**
     * Aggregate inbound and outbound mail telemetry for the SecOps Queue & Workers monitor.
     *
     * @return array{counts: array<string, int>, items: list<array<string, mixed>>}
     */
    public function getMailQueueTelemetry(int $limit = 25): array
    {
        $byId = [];

        if ($this->isMongo()) {
            try {
                $docs = $this->db->mongoFind('mail_messages', [], ['sort' => ['created_at' => -1], 'limit' => max($limit * 3, 100)]);
                foreach ($docs as $doc) {
                    $norm = $this->normalizeMessage($doc);
                    if ($norm['id'] !== '') {
                        $byId[$norm['id']] = $norm;
                    }
                }
            } catch (\Throwable) {}
        } elseif ($this->isSqlAlive()) {
            try {
                $rows = $this->db->fetchAll('SELECT * FROM mail_messages ORDER BY created_at DESC LIMIT ?', [max($limit * 3, 100)]);
                foreach ($rows as $row) {
                    $norm = $this->normalizeMessage($row);
                    if ($norm['id'] !== '') {
                        $byId[$norm['id']] = $norm;
                    }
                }
            } catch (\Throwable) {}
        }

        foreach ($this->readJsonStore('messages.json') as $rawMsg) {
            $norm = $this->normalizeMessage($rawMsg);
            if ($norm['id'] !== '' && !isset($byId[$norm['id']])) {
                $byId[$norm['id']] = $norm;
            }
        }

        $messages = array_values($byId);
        usort($messages, static fn(array $a, array $b): int => strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? '')));

        $counts = [
            'total' => 0,
            'inbound' => 0,
            'outbound' => 0,
            'scanning' => 0,
            'delivered' => 0,
            'quarantined' => 0,
            'drafts' => 0,
        ];
        $items = [];

        foreach ($messages as $msg) {
            $folder = strtolower((string)($msg['folder'] ?? 'inbox'));
            $scanStatus = strtolower((string)($msg['scan_status'] ?? 'clean'));
            $fromAddr = strtolower((string)($msg['from_address'] ?? ''));
            $mailboxAddr = strtolower((string)($msg['mailbox_address'] ?? ''));

            if (in_array($folder, ['sent', 'drafts'], true)) {
                $direction = 'OUTBOUND';
            } elseif (in_array($folder, ['inbox', 'quarantine'], true)) {
                $direction = 'INBOUND';
            } else {
                $direction = ($fromAddr !== '' && $fromAddr === $mailboxAddr) ? 'OUTBOUND' : 'INBOUND';
            }

            if ($folder === 'drafts') {
                $queueStatus = 'DRAFT';
                $counts['drafts']++;
            } elseif (in_array($scanStatus, ['pending', 'scanning'], true)) {
                $queueStatus = 'SCANNING';
                $counts['scanning']++;
            } elseif ($folder === 'quarantine' || in_array($scanStatus, ['infected', 'suspicious', 'quarantined'], true)) {
                $queueStatus = 'QUARANTINED';
                $counts['quarantined']++;
            } elseif ($folder === 'trash') {
                $queueStatus = 'TRASHED';
            } else {
                $queueStatus = 'DELIVERED';
                $counts['delivered']++;
            }

            $counts['total']++;
            if ($direction === 'OUTBOUND') {
                $counts['outbound']++;
            } else {
                $counts['inbound']++;
            }

            if (count($items) < $limit) {
                $items[] = [
                    'id' => $msg['id'],
                    'direction' => $direction,
                    'from_address' => (string)($msg['from_address'] ?? ''),
                    'to_address' => (string)($msg['to_address'] ?? ''),
                    'subject' => (string)($msg['subject'] ?? '(No Subject)'),
                    'folder' => $folder,
                    'scan_status' => $scanStatus,
                    'spam_score' => (float)($msg['spam_score'] ?? 0.0),
                    'spam_action' => (string)($msg['spam_action'] ?? 'no action'),
                    'queue_status' => $queueStatus,
                    'scanned_at' => (string)($msg['scanned_at'] ?? ''),
                    'created_at' => (string)($msg['created_at'] ?? ''),
                ];
            }
        }

        return [
            'counts' => $counts,
            'items' => $items,
        ];
    }

    private function isMongo(): bool
    {
        return $this->db !== null && $this->db->getDriver() === 'mongodb' && $this->db->isAlive();
    }

    private function isSqlAlive(): bool
    {
        return $this->db !== null && $this->db->isSql() && $this->db->isAlive();
    }

    private function readJsonStore(string $filename): array
    {
        $path = $this->storeDir . '/' . $filename;
        if (!is_file($path) || !is_readable($path)) {
            return [];
        }
        $raw = @file_get_contents($path);
        if (!is_string($raw) || $raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function withStoreLock(string $filename, callable $mutator): void
    {
        if (!$this->isOwnedWritableDir($this->storeDir)) {
            $this->storeDir = $this->ensureFallbackStoreDir();
        }

        $path = $this->storeDir . '/' . $filename;
        $lockPath = $path . '.lock';
        $fp = @fopen($lockPath, 'c+');
        if ($fp === false) {
            $this->storeDir = $this->ensureFallbackStoreDir();
            $path = $this->storeDir . '/' . $filename;
            $lockPath = $path . '.lock';
            $fp = @fopen($lockPath, 'c+');
        }
        @chmod($lockPath, 0600);
        if ($fp !== false) {
            @flock($fp, LOCK_EX);
        }

        try {
            $current = $this->readJsonStore($filename);
            $next = $mutator($current);
            $encoded = json_encode($next, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $written = @file_put_contents($path, $encoded, LOCK_EX);
            if ($written === false) {
                $this->storeDir = $this->ensureFallbackStoreDir();
                $path = $this->storeDir . '/' . $filename;
                file_put_contents($path, $encoded, LOCK_EX);
            }
            @chmod($path, 0600);
        } finally {
            if ($fp !== false) {
                @flock($fp, LOCK_UN);
                @fclose($fp);
            }
        }
    }
}

