<?php
declare(strict_types=1);

namespace Nexus\Mail;

/**
 * Controlled Ephemeral Reader Jail
 *
 * Isolates opened email content inside an ephemeral container under storage/cache/jails/<jail_id>
 * paired with a zero-privilege CSP sandbox document. Automatically expires after 15 minutes
 * and immediately shreds + unlinks the container when reading finishes.
 */
class MailJail
{
    public const DEFAULT_TTL_SECONDS = 900;

    private string $jailRoot;
    private int $ttlSeconds;

    public function __construct(?string $jailRoot = null, int $ttlSeconds = self::DEFAULT_TTL_SECONDS)
    {
        $this->jailRoot = rtrim($jailRoot ?? (dirname(__DIR__, 2) . '/storage/cache/jails'), '/');
        $this->ttlSeconds = max(60, $ttlSeconds);
    }

    /**
     * Open a controlled isolated jail container for reading a single scanned email.
     */
    public function open(string $userId, array $message, array $scanReport): array
    {
        $this->pruneExpired();

        if (!is_dir($this->jailRoot)) {
            @mkdir($this->jailRoot, 0777, true);
        }
        @chmod($this->jailRoot, 0777);

        $now = time();
        $expiresAt = $now + $this->ttlSeconds;
        $jailId = 'jail_' . bin2hex(random_bytes(12));
        $dir = $this->jailRoot . '/' . $jailId;

        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        @chmod($dir, 0777);

        $sandboxHtml = $this->buildSandboxedDocument($message, $scanReport);

        $meta = [
            'jail_id' => $jailId,
            'user_id' => $userId,
            'message_id' => (string)($message['id'] ?? ''),
            'created_at' => $now,
            'created_iso' => gmdate('Y-m-d\TH:i:s\Z', $now),
            'expires_at' => $expiresAt,
            'expires_iso' => gmdate('Y-m-d\TH:i:s\Z', $expiresAt),
            'ttl_seconds' => $this->ttlSeconds,
            'scan_status' => (string)($scanReport['status'] ?? 'clean'),
            'threats' => (array)($scanReport['threats'] ?? []),
        ];

        $metaPath = $dir . '/meta.json';
        $contentPath = $dir . '/content.html';

        file_put_contents($metaPath, json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
        @chmod($metaPath, 0666);

        file_put_contents($contentPath, $sandboxHtml, LOCK_EX);
        @chmod($contentPath, 0666);

        return [
            'jail_id' => $jailId,
            'message_id' => $meta['message_id'],
            'ttl_seconds' => $this->ttlSeconds,
            'expires_at' => $expiresAt,
            'expires_iso' => $meta['expires_iso'],
            'isolated_path' => 'storage/cache/jails/' . $jailId,
            'scan_report' => $scanReport,
            'sandbox_html' => $sandboxHtml,
        ];
    }

    /**
     * Retrieve an active reader jail container verified for the given owner.
     */
    public function get(string $userId, string $jailId): ?array
    {
        if (!$this->isValidJailId($jailId)) {
            return null;
        }

        $dir = $this->jailRoot . '/' . $jailId;
        $metaPath = $dir . '/meta.json';
        $contentPath = $dir . '/content.html';

        if (!is_file($metaPath) || !is_file($contentPath)) {
            return null;
        }

        $meta = json_decode((string)file_get_contents($metaPath), true);
        if (!is_array($meta) || ($meta['user_id'] ?? '') !== $userId) {
            return null;
        }

        if ((int)($meta['expires_at'] ?? 0) < time()) {
            $this->shredDirectory($dir);
            return null;
        }

        $meta['sandbox_html'] = (string)file_get_contents($contentPath);
        return $meta;
    }

    /**
     * Immediately shred and tear down the reader jail when the user closes or exits the message.
     */
    public function destroy(string $userId, string $jailId): bool
    {
        if (!$this->isValidJailId($jailId)) {
            return false;
        }

        $dir = $this->jailRoot . '/' . $jailId;
        $metaPath = $dir . '/meta.json';
        if (!is_dir($dir)) {
            return false;
        }

        if (is_file($metaPath)) {
            $meta = json_decode((string)file_get_contents($metaPath), true);
            if (!is_array($meta) || ($meta['user_id'] ?? '') !== $userId) {
                return false;
            }
        }

        return $this->shredDirectory($dir);
    }

    /**
     * Destroy all active jails belonging to a specific user (e.g., before opening a new message).
     */
    public function destroyAllForUser(string $userId): int
    {
        if (!is_dir($this->jailRoot)) {
            return 0;
        }

        $count = 0;
        foreach (glob($this->jailRoot . '/jail_*') ?: [] as $dir) {
            if (!is_dir($dir)) {
                continue;
            }
            $metaPath = $dir . '/meta.json';
            $meta = is_file($metaPath) ? json_decode((string)file_get_contents($metaPath), true) : null;
            if (is_array($meta) && ($meta['user_id'] ?? '') === $userId) {
                if ($this->shredDirectory($dir)) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Reap expired jail containers left over from interrupted client sessions.
     */
    public function pruneExpired(?int $now = null): int
    {
        if (!is_dir($this->jailRoot)) {
            return 0;
        }

        $now = $now ?? time();
        $pruned = 0;

        foreach (glob($this->jailRoot . '/jail_*') ?: [] as $dir) {
            if (!is_dir($dir)) {
                continue;
            }

            $metaPath = $dir . '/meta.json';
            $meta = is_file($metaPath) ? json_decode((string)file_get_contents($metaPath), true) : null;

            if (!is_array($meta) || (int)($meta['expires_at'] ?? 0) < $now) {
                if ($this->shredDirectory($dir)) {
                    $pruned++;
                }
            }
        }

        return $pruned;
    }

    private function buildSandboxedDocument(array $message, array $scanReport): string
    {
        $status = (string)($scanReport['status'] ?? 'clean');
        $threats = (array)($scanReport['threats'] ?? []);
        $sanitizedBody = (string)($scanReport['sanitized_html'] ?? '<p>(Empty message)</p>');

        $warningHtml = '';
        if ($status !== 'clean' && !empty($threats)) {
            $items = '';
            foreach ($threats as $threat) {
                $items .= '<li>' . htmlspecialchars((string)$threat, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</li>';
            }
            $bg = $status === 'infected' ? '#fff1f2' : '#fffbeb';
            $border = $status === 'infected' ? '#fda4af' : '#fcd34d';
            $color = $status === 'infected' ? '#9f1239' : '#92400e';
            $label = $status === 'infected'
                ? 'AV Scanner Quarantined Threats (Neutralized Before Render)'
                : 'Suspicious Indicators Neutralized Inside Jail';

            $warningHtml = '<div style="margin:0 0 16px 0;padding:12px 14px;border-radius:8px;background:' . $bg . ';border:1px solid ' . $border . ';color:' . $color . ';font-size:13px;">'
                . '<strong>' . $label . ':</strong>'
                . '<ul style="margin:6px 0 0 18px;padding:0;">' . $items . '</ul>'
                . '</div>';
        }

        $attachments = (array)($message['attachments'] ?? []);
        $attHtml = '';
        if (!empty($attachments)) {
            $pills = [];
            foreach ($attachments as $att) {
                $name = is_array($att) ? (string)($att['name'] ?? 'attachment') : (string)$att;
                $pills[] = '<span style="display:inline-block;padding:4px 10px;border-radius:999px;background:#f1f5f9;border:1px solid #cbd5e1;font-size:12px;color:#334155;">'
                    . htmlspecialchars($name, ENT_QUOTES | ENT_HTML5, 'UTF-8')
                    . ' (Execution Blocked)</span>';
            }
            $attHtml = '<div style="margin-top:18px;padding-top:12px;border-top:1px solid #e2e8f0;display:flex;gap:8px;flex-wrap:wrap;">'
                . implode(' ', $pills)
                . '</div>';
        }

        return '<!DOCTYPE html><html lang="en"><head>'
            . '<meta charset="UTF-8">'
            . '<meta http-equiv="Content-Security-Policy" content="default-src \'none\'; style-src \'unsafe-inline\'; img-src data:;">'
            . '<style>'
            . 'body{margin:0;padding:20px 24px;font-family:"Segoe UI",system-ui,-apple-system,sans-serif;font-size:14.5px;line-height:1.62;color:#0f172a;background:#ffffff;word-break:break-word;}'
            . 'a{color:#0369a1;text-decoration:underline;}'
            . 'pre,code{font-family:Consolas,Menlo,monospace;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:2px 6px;font-size:13px;}'
            . 'pre{padding:12px;overflow-x:auto;}'
            . 'blockquote{margin:12px 0;padding:8px 14px;border-left:3px solid #94a3b8;color:#475569;background:#f8fafc;}'
            . '</style></head><body>'
            . $warningHtml
            . $sanitizedBody
            . $attHtml
            . '</body></html>';
    }

    private function isValidJailId(string $jailId): bool
    {
        return (bool)preg_match('/^jail_[a-f0-9]{24}$/', $jailId);
    }

    private function shredDirectory(string $dir): bool
    {
        if (!is_dir($dir)) {
            return false;
        }

        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir . '/' . $entry;
            if (is_file($path)) {
                $size = max(64, (int)@filesize($path));
                @file_put_contents($path, random_bytes(min($size, 8192)), LOCK_EX);
                @unlink($path);
            }
        }

        return @rmdir($dir);
    }
}

