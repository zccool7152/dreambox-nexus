<?php
declare(strict_types=1);

namespace Nexus\Mail;

use InvalidArgumentException;
use Nexus\Core\Env;
use Nexus\Database\Database;
use RuntimeException;

/**
 * Hybrid Webmail Repository (MongoDB + SQL + Atomic JSON Store Fallback)
 *
 * Manages per-user mailbox claims (@MAIL_DOMAIN / nexus.local fallback) and
 * scanned message storage across folders (inbox, starred, sent, drafts, trash).
 */
class MailRepository
{
    private ?Database $db;
    private string $storeDir;

    public const VALID_FOLDERS = ['inbox', 'starred', 'sent', 'drafts', 'trash'];

    public function __construct(?Database $db = null, ?string $storeDir = null)
    {
        $this->db = $db;
        $this->storeDir = rtrim($storeDir ?? (dirname(__DIR__, 2) . '/storage/app/private/mail'), '/');
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

        $existingOwn = $this->getMailboxForUser($userId);
        if ($existingOwn !== null) {
            return $existingOwn;
        }

        $targetDomain = $domain !== null && trim($domain) !== ''
            ? strtolower(trim($domain, " \t\n\r\0\x0B@"))
            : self::getMailDomain();
        $address = $cleanLocal . '@' . $targetDomain;

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

        $this->withStoreLock('mailboxes.json', function (array $mailboxes) use ($record): array {
            $mailboxes[] = $record;
            return $mailboxes;
        });

        if ($this->isMongo()) {
            $this->db->mongoInsert('mailboxes', $record);
        } elseif ($this->isSqlAlive()) {
            try {
                $this->db->execute(
                    'INSERT INTO mailboxes (id, user_id, address, local_part, domain, created_at) VALUES (?, ?, ?, ?, ?, ?)',
                    [$record['id'], $record['user_id'], $record['address'], $record['local_part'], $record['domain'], $record['created_at']]
                );
            } catch (\Throwable) {}
        }

        $this->seedInitialMessages($userId, $address, $targetDomain);

        return $this->normalizeMailbox($record);
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

        $record = [
            'id' => (string)($message['id'] ?? ('msg_' . bin2hex(random_bytes(8)))),
            'owner_user_id' => (string)($message['owner_user_id'] ?? ''),
            'mailbox_address' => strtolower(trim((string)($message['mailbox_address'] ?? ''))),
            'folder' => (string)($message['folder'] ?? 'inbox'),
            'from_name' => (string)($message['from_name'] ?? ''),
            'from_address' => strtolower(trim((string)($message['from_address'] ?? ''))),
            'to_address' => strtolower(trim((string)($message['to_address'] ?? ''))),
            'subject' => (string)($message['subject'] ?? '(No Subject)'),
            'body_text' => (string)($message['body_text'] ?? ''),
            'body_html' => $scan['sanitized_html'],
            'attachments' => (array)($message['attachments'] ?? []),
            'is_read' => !empty($message['is_read']),
            'is_starred' => !empty($message['is_starred']),
            'scan_status' => $scan['status'],
            'scan_threats' => $scan['threats'],
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

        $this->withStoreLock('messages.json', function (array $messages) use ($userId, $messageId, $fields, &$updated): array {
            foreach ($messages as $i => $msg) {
                if (($msg['id'] ?? '') === $messageId && ($msg['owner_user_id'] ?? '') === $userId) {
                    foreach (['is_read', 'is_starred', 'folder', 'subject', 'to_address', 'body_text', 'body_html'] as $k) {
                        if (array_key_exists($k, $fields)) {
                            $messages[$i][$k] = $fields[$k];
                        }
                    }
                    $updated = $this->normalizeMessage($messages[$i]);
                    break;
                }
            }
            return $messages;
        });

        if ($updated !== null && $this->isMongo()) {
            $mongoSet = [];
            foreach (['is_read', 'is_starred', 'folder', 'subject', 'to_address', 'body_text', 'body_html'] as $k) {
                if (array_key_exists($k, $fields)) {
                    $mongoSet[$k] = $fields[$k];
                }
            }
            if (!empty($mongoSet)) {
                $this->db->mongoUpdate('mail_messages', ['id' => $messageId, 'owner_user_id' => $userId], ['$set' => $mongoSet]);
            }
        } elseif ($updated !== null && $this->isSqlAlive()) {
            try {
                $sets = [];
                $params = [];
                foreach (['is_read', 'is_starred', 'folder', 'subject', 'to_address', 'body_text', 'body_html'] as $k) {
                    if (array_key_exists($k, $fields)) {
                        $sets[] = "{$k} = ?";
                        $params[] = is_bool($fields[$k]) ? ($fields[$k] ? 1 : 0) : $fields[$k];
                    }
                }
                if (!empty($sets)) {
                    $params[] = $messageId;
                    $params[] = $userId;
                    $this->db->execute('UPDATE mail_messages SET ' . implode(', ', $sets) . ' WHERE id = ? AND owner_user_id = ?', $params);
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

        if ($deleted && $this->isMongo()) {
            $this->db->mongoDelete('mail_messages', ['id' => $messageId, 'owner_user_id' => $userId]);
        } elseif ($deleted && $this->isSqlAlive()) {
            try {
                $this->db->execute('DELETE FROM mail_messages WHERE id = ? AND owner_user_id = ?', [$messageId, $userId]);
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

    private function normalizeMailbox(array $mb): array
    {
        return [
            'id' => (string)($mb['id'] ?? $mb['_id'] ?? ''),
            'user_id' => (string)($mb['user_id'] ?? ''),
            'address' => strtolower((string)($mb['address'] ?? '')),
            'local_part' => strtolower((string)($mb['local_part'] ?? '')),
            'domain' => strtolower((string)($mb['domain'] ?? self::getMailDomain())),
            'created_at' => (string)($mb['created_at'] ?? ''),
        ];
    }

    private function normalizeMessage(array $m): array
    {
        $attachments = $m['attachments'] ?? [];
        if (is_string($attachments)) {
            $attachments = json_decode($attachments, true) ?: [];
        }
        $threats = $m['scan_threats'] ?? [];
        if (is_string($threats)) {
            $threats = json_decode($threats, true) ?: [];
        }

        return [
            'id' => (string)($m['id'] ?? $m['_id'] ?? ''),
            'owner_user_id' => (string)($m['owner_user_id'] ?? ''),
            'mailbox_address' => (string)($m['mailbox_address'] ?? ''),
            'folder' => (string)($m['folder'] ?? 'inbox'),
            'from_name' => (string)($m['from_name'] ?? ''),
            'from_address' => (string)($m['from_address'] ?? ''),
            'to_address' => (string)($m['to_address'] ?? ''),
            'subject' => (string)($m['subject'] ?? '(No Subject)'),
            'body_text' => (string)($m['body_text'] ?? ''),
            'body_html' => (string)($m['body_html'] ?? ''),
            'attachments' => is_array($attachments) ? $attachments : [],
            'is_read' => !empty($m['is_read']),
            'is_starred' => !empty($m['is_starred']),
            'scan_status' => (string)($m['scan_status'] ?? 'clean'),
            'scan_threats' => is_array($threats) ? $threats : [],
            'scanned_at' => (string)($m['scanned_at'] ?? ''),
            'created_at' => (string)($m['created_at'] ?? ''),
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
        if (!is_file($path)) {
            return [];
        }
        $decoded = json_decode((string)file_get_contents($path), true);
        return is_array($decoded) ? $decoded : [];
    }

    private function withStoreLock(string $filename, callable $mutator): void
    {
        if (!is_dir($this->storeDir)) {
            @mkdir($this->storeDir, 0750, true);
        }

        $path = $this->storeDir . '/' . $filename;
        $lockPath = $path . '.lock';
        $fp = @fopen($lockPath, 'c+');
        if ($fp !== false) {
            @flock($fp, LOCK_EX);
        }

        try {
            $current = $this->readJsonStore($filename);
            $next = $mutator($current);
            file_put_contents(
                $path,
                json_encode($next, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                LOCK_EX
            );
        } finally {
            if ($fp !== false) {
                @flock($fp, LOCK_UN);
                @fclose($fp);
            }
        }
    }
}
