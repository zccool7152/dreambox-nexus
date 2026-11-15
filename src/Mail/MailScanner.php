<?php
declare(strict_types=1);

namespace Nexus\Mail;

use Nexus\Core\Env;

/**
 * Nexus Antivirus, Anti-Spam & Heuristic Threat Scanner
 *
 * Combines ClamAV (clamd zINSTREAM socket + clamscan CLI fallback),
 * Rspamd (HTTP /checkv2 + rspamc CLI + heuristic symbol scoring fallback),
 * and strict DOMDocument HTML allowlist sanitization before rendering inside
 * the isolated Ephemeral Reader Jail.
 */
class MailScanner
{
    public const EICAR_SIGNATURE = 'X5O!P%@AP[4\PZX54(P^)7CC)7}$EICAR-STANDARD-ANTIVIRUS-TEST-FILE!$H+H*';

    private const DANGEROUS_EXTENSIONS = [
        'exe', 'bat', 'cmd', 'scr', 'ps1', 'vbs', 'vbe',
        'hta', 'js', 'jse', 'jar', 'msi', 'dll', 'pif', 'lnk', 'com',
    ];

    private const ALLOWED_HTML_ELEMENTS = [
        'html', 'body', 'div', 'p', 'br', 'hr', 'span', 'strong', 'b', 'em', 'i', 'u',
        'h1', 'h2', 'h3', 'h4', 'ul', 'ol', 'li', 'blockquote', 'pre', 'code',
        'table', 'thead', 'tbody', 'tr', 'th', 'td', 'a',
    ];

    private const ALLOWED_ATTRIBUTES = [
        'href', 'title', 'colspan', 'rowspan',
    ];

    private string $clamHost;
    private int $clamPort;
    private string $clamSocket;
    private string $rspamdHost;
    private int $rspamdPort;
    private ?string $rspamdPassword;
    private float $spamThreshold;
    private float $rejectThreshold;
    private float $socketTimeout;

    public function __construct(
        ?string $clamHost = null,
        ?int $clamPort = null,
        ?string $clamSocket = null,
        ?string $rspamdHost = null,
        ?int $rspamdPort = null,
        ?string $rspamdPassword = null,
        ?float $spamThreshold = null,
        ?float $rejectThreshold = null,
        ?float $socketTimeout = null
    ) {
        $this->clamHost = trim((string)($clamHost ?? Env::get('CLAMAV_HOST', '127.0.0.1')));
        $this->clamPort = $clamPort ?? (int)Env::get('CLAMAV_PORT', 3310);
        $this->clamSocket = trim((string)($clamSocket ?? Env::get('CLAMAV_SOCKET', '/var/run/clamav/clamd.ctl')));
        $this->rspamdHost = trim((string)($rspamdHost ?? Env::get('RSPAMD_HOST', '127.0.0.1')));
        $this->rspamdPort = $rspamdPort ?? (int)Env::get('RSPAMD_PORT', 11333);

        $rawPassword = $rspamdPassword ?? Env::get('RSPAMD_PASSWORD', null);
        $this->rspamdPassword = is_string($rawPassword) && trim($rawPassword) !== '' ? trim($rawPassword) : null;

        $this->spamThreshold = $spamThreshold ?? (float)Env::get('RSPAMD_SPAM_THRESHOLD', 6.0);
        $this->rejectThreshold = $rejectThreshold ?? (float)Env::get('RSPAMD_REJECT_THRESHOLD', 12.0);

        $hasExplicitEndpoint = ($clamHost !== null || $clamPort !== null || $rspamdHost !== null || $rspamdPort !== null);
        $this->socketTimeout = $socketTimeout ?? ($hasExplicitEndpoint ? 1.5 : 0.35);
    }

    /**
     * Scan email content and attachments via ClamAV, Rspamd, and HTML sanitization heuristics.
     */
    public function scan(string $subject, string $textBody, string $htmlBody = '', mixed $attachments = []): array
    {
        $threats = [];
        $severity = 0; // 0 = clean, 1 = suspicious, 2 = infected
        $normalizedAttachments = self::normalizeAttachments($attachments);

        $combined = $subject . "\n" . $textBody . "\n" . $htmlBody;
        $signals = [
            'eicar' => false,
            'clam_virus' => false,
            'script_tag' => false,
            'active_container' => false,
            'event_handler' => false,
            'malicious_uri' => false,
            'dropper_cmd' => false,
            'dangerous_attachment' => false,
        ];

        // 1. Built-in EICAR Malware Signature Check
        if (str_contains($combined, self::EICAR_SIGNATURE)) {
            $threats[] = 'Malware Signature Detected: EICAR-Standard-Antivirus-Test-File';
            $severity = 2;
            $signals['eicar'] = true;
        }

        // 2. Active Script / Payload Injection Signatures
        if (preg_match('/<script\b/i', $combined)) {
            $threats[] = 'Executable HTML <script> Payload Stripped';
            $severity = max($severity, 2);
            $signals['script_tag'] = true;
        }

        if (preg_match('/<\s*(iframe|frame|frameset|object|embed|applet|base|meta|link)\b/i', $htmlBody)) {
            $threats[] = 'Embedded Active Container Tag Blocked';
            $severity = max($severity, 1);
            $signals['active_container'] = true;
        }

        if (preg_match('/\bon[a-z]+\s*=/i', $htmlBody)) {
            $threats[] = 'Inline DOM Event Handler (onload/onerror/onclick) Stripped';
            $severity = max($severity, 1);
            $signals['event_handler'] = true;
        }

        $decodedForUriCheck = $this->decodeEntitiesIteratively($combined);
        $strippedForUriCheck = preg_replace('/[\x00-\x20]+/', '', $decodedForUriCheck) ?? $decodedForUriCheck;
        if (
            preg_match('/(?:javascript|vbscript)\s*:|data\s*:\s*(?:text\/html|image\/svg\+xml)/i', $decodedForUriCheck)
            || preg_match('/(?:javascript|vbscript):|data:(?:text\/html|image\/svg\+xml)/i', $strippedForUriCheck)
        ) {
            $threats[] = 'Malicious Script/HTML Data URI Neutralized';
            $severity = max($severity, 2);
            $signals['malicious_uri'] = true;
        }

        if (preg_match('/(?:eval\s*\(\s*base64_decode|powershell\s+-e(?:nc|ncodedcommand)?|WScript\.Shell|cmd\.exe\s+\/c)/i', $combined)) {
            $threats[] = 'Obfuscated Shell/Dropper Command Indicator';
            $severity = max($severity, 2);
            $signals['dropper_cmd'] = true;
        }

        // 3. Attachment Extension & Subject Double-Extension Inspection (stdClass-safe)
        foreach ($normalizedAttachments as $att) {
            if (is_object($att)) {
                $att = (array)$att;
            }
            $name = is_array($att) ? (string)($att['name'] ?? '') : (is_scalar($att) ? (string)$att : '');
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if ($ext !== '' && in_array($ext, self::DANGEROUS_EXTENSIONS, true)) {
                $threats[] = "Hazardous Executable/Script Attachment: {$name}";
                $severity = max($severity, 2);
                $signals['dangerous_attachment'] = true;
            }
        }

        if (preg_match('/\b[a-z0-9._-]+\.(?:exe|scr|bat|ps1|vbs|hta)\b/i', $subject, $subjMatch)) {
            $threats[] = "Suspicious Executable Filename Referenced: {$subjMatch[0]}";
            $severity = max($severity, 1);
        }

        // 4. ClamAV Scan (clamd zINSTREAM socket -> clamscan/clamdscan CLI -> builtin signatures)
        $clamResult = $this->scanWithClamAv($combined, $normalizedAttachments, $signals['eicar']);
        if (!empty($clamResult['infected'])) {
            $sig = (string)($clamResult['signature'] ?? 'Malware.Generic');
            $threats[] = "ClamAV Signature Match: {$sig}";
            $severity = 2;
            $signals['clam_virus'] = true;
        }

        // 5. Rspamd Anti-Spam / Phishing Scan (/checkv2 HTTP -> rspamc CLI -> heuristic scoring fallback)
        $rspamdResult = $this->scanWithRspamd($subject, $textBody, $htmlBody, $signals);
        $spamScore = (float)($rspamdResult['score'] ?? 0.0);
        $spamAction = strtolower((string)($rspamdResult['action'] ?? 'no action'));

        if ($spamAction === 'reject' || $spamScore >= $this->rejectThreshold) {
            $threats[] = sprintf('Rspamd Rejected Message (Score: %.1f, Action: %s)', $spamScore, $spamAction);
            $severity = max($severity, 2);
        } elseif (
            in_array($spamAction, ['add header', 'rewrite subject', 'greylist', 'soft reject'], true)
            || $spamScore >= $this->spamThreshold
        ) {
            $threats[] = sprintf('Rspamd Spam/Phishing Alert (Score: %.1f, Action: %s)', $spamScore, $spamAction);
            $severity = max($severity, 1);
        }

        $status = match ($severity) {
            2 => 'infected',
            1 => 'suspicious',
            default => 'clean',
        };

        $sourceHtml = trim($htmlBody) !== ''
            ? $htmlBody
            : '<p>' . nl2br(htmlspecialchars($textBody, ENT_QUOTES | ENT_HTML5, 'UTF-8')) . '</p>';

        return [
            'status' => $status,
            'threats' => array_values(array_unique($threats)),
            'scanner' => 'ClamAV + Rspamd + Nexus Heuristic Engine',
            'scanned_at' => gmdate('Y-m-d H:i:s') . ' UTC',
            'sanitized_html' => $this->sanitizeHtml($sourceHtml),
            'clamav' => $clamResult,
            'rspamd' => $rspamdResult,
            'spam_score' => $spamScore,
            'spam_action' => $spamAction,
        ];
    }

    /**
     * Scan raw message payload and attachment files through ClamAV clamd zINSTREAM,
     * falling back to clamscan/clamdscan CLI or built-in signatures.
     */
    private function scanWithClamAv(string $payload, array $attachments, bool $hasEicarSignature): array
    {
        $instreamResult = $this->clamdInstreamScan($payload);
        if ($instreamResult !== null) {
            if (empty($instreamResult['infected'])) {
                foreach ($attachments as $att) {
                    if (is_array($att) && !empty($att['tmp_path']) && is_string($att['tmp_path']) && is_file($att['tmp_path'])) {
                        $attBytes = @file_get_contents($att['tmp_path']);
                        if (is_string($attBytes) && $attBytes !== '') {
                            $attRes = $this->clamdInstreamScan($attBytes);
                            if ($attRes !== null && !empty($attRes['infected'])) {
                                return $attRes;
                            }
                        }
                    }
                }
            }
            return $instreamResult;
        }

        $binary = $this->findClamScanBinary();
        if ($binary !== null) {
            $cliHit = $this->scanRawWithClamIfInstalled($payload, $binary);
            if ($cliHit === null) {
                foreach ($attachments as $att) {
                    if (is_array($att) && !empty($att['tmp_path']) && is_string($att['tmp_path'])) {
                        $cliHit = $this->runClamScan($att['tmp_path'], $binary);
                        if ($cliHit !== null) {
                            break;
                        }
                    }
                }
            }

            return [
                'engine' => 'ClamAV',
                'driver' => 'clamscan_cli',
                'infected' => $cliHit !== null,
                'signature' => $cliHit,
            ];
        }

        return [
            'engine' => 'ClamAV',
            'driver' => 'builtin_signatures',
            'infected' => $hasEicarSignature,
            'signature' => $hasEicarSignature ? 'Win.Test.EICAR_HDB-1' : null,
        ];
    }

    /**
     * Connect to clamd UNIX/TCP socket using the zINSTREAM length-prefixed framing protocol.
     */
    private function clamdInstreamScan(string $payload): ?array
    {
        $targets = [];
        if ($this->clamSocket !== '' && @file_exists($this->clamSocket)) {
            $targets[] = 'unix://' . $this->clamSocket;
        }
        if ($this->clamHost !== '' && $this->clamPort > 0) {
            $targets[] = 'tcp://' . $this->clamHost . ':' . $this->clamPort;
        }

        foreach ($targets as $target) {
            $errno = 0;
            $errstr = '';
            $sock = @stream_socket_client($target, $errno, $errstr, $this->socketTimeout);
            if (!is_resource($sock)) {
                continue;
            }

            try {
                stream_set_timeout($sock, max(1, (int)ceil($this->socketTimeout * 4)));
                if (!$this->streamWriteAll($sock, "zINSTREAM\0")) {
                    continue;
                }

                $len = strlen($payload);
                $offset = 0;
                $chunkSize = 65536;
                while ($offset < $len) {
                    $chunk = substr($payload, $offset, $chunkSize);
                    $packet = pack('N', strlen($chunk)) . $chunk;
                    if (!$this->streamWriteAll($sock, $packet)) {
                        continue 2;
                    }
                    $offset += strlen($chunk);
                }

                if (!$this->streamWriteAll($sock, pack('N', 0))) {
                    continue;
                }

                $response = '';
                while (!feof($sock)) {
                    $buf = @fread($sock, 4096);
                    if ($buf === false || $buf === '') {
                        break;
                    }
                    $response .= $buf;
                    if (str_contains($buf, "\0")) {
                        break;
                    }
                }

                $cleanResp = trim(str_replace("\0", '', $response));
                if ($cleanResp === '') {
                    continue;
                }

                if (preg_match('/^stream:\s*(.+?)\s+FOUND$/i', $cleanResp, $m)) {
                    return [
                        'engine' => 'ClamAV',
                        'driver' => 'clamd_instream',
                        'infected' => true,
                        'signature' => trim($m[1]),
                    ];
                }

                if (preg_match('/^stream:\s*OK$/i', $cleanResp)) {
                    return [
                        'engine' => 'ClamAV',
                        'driver' => 'clamd_instream',
                        'infected' => false,
                        'signature' => null,
                    ];
                }
            } finally {
                @fclose($sock);
            }
        }

        return null;
    }

    /**
     * Evaluate spam and phishing indicators via Rspamd /checkv2 HTTP API, rspamc CLI,
     * or the deterministic Rspamd-compatible heuristic symbol engine when daemon is offline.
     */
    private function scanWithRspamd(string $subject, string $textBody, string $htmlBody, array $signals): array
    {
        $rawMessage = $this->buildRfc5322Payload($subject, $textBody, $htmlBody);

        $httpResult = $this->rspamdHttpScan($rawMessage);
        if ($httpResult !== null) {
            return $httpResult;
        }

        $cliResult = $this->rspamcCliScan($rawMessage);
        if ($cliResult !== null) {
            return $cliResult;
        }

        return $this->rspamdHeuristicFallback($subject, $textBody, $htmlBody, $signals);
    }

    private function rspamdHttpScan(string $rawMessage): ?array
    {
        if ($this->rspamdHost === '' || $this->rspamdPort <= 0) {
            return null;
        }

        $errno = 0;
        $errstr = '';
        $sock = @stream_socket_client(
            'tcp://' . $this->rspamdHost . ':' . $this->rspamdPort,
            $errno,
            $errstr,
            $this->socketTimeout
        );
        if (!is_resource($sock)) {
            return null;
        }

        try {
            stream_set_timeout($sock, max(1, (int)ceil($this->socketTimeout * 4)));

            $headers = [
                'POST /checkv2 HTTP/1.1',
                'Host: ' . $this->rspamdHost . ':' . $this->rspamdPort,
                'Content-Type: text/plain; charset=utf-8',
                'Content-Length: ' . strlen($rawMessage),
                'Connection: close',
            ];
            if ($this->rspamdPassword !== null) {
                $headers[] = 'Password: ' . $this->rspamdPassword;
            }

            $httpRequest = implode("\r\n", $headers) . "\r\n\r\n" . $rawMessage;
            if (!$this->streamWriteAll($sock, $httpRequest)) {
                return null;
            }

            $rawResponse = '';
            while (!feof($sock)) {
                $buf = @fread($sock, 8192);
                if ($buf === false || $buf === '') {
                    break;
                }
                $rawResponse .= $buf;
            }

            $parts = preg_split('/\r?\n\r?\n/', $rawResponse, 2);
            if (!is_array($parts) || count($parts) < 2) {
                return null;
            }

            [$head, $body] = $parts;
            if (!preg_match('/^HTTP\/1\.[01]\s+2\d\d\b/i', $head)) {
                return null;
            }

            if (preg_match('/^Transfer-Encoding:\s*chunked\b/im', $head)) {
                $body = $this->decodeChunkedHttpBody($body);
            }

            $decoded = json_decode(trim($body), true);
            if (!is_array($decoded)) {
                return null;
            }

            return [
                'engine' => 'Rspamd',
                'driver' => 'rspamd_http',
                'score' => round((float)($decoded['score'] ?? 0.0), 2),
                'required_score' => round((float)($decoded['required_score'] ?? $this->spamThreshold), 2),
                'action' => strtolower((string)($decoded['action'] ?? 'no action')),
                'symbols' => is_array($decoded['symbols'] ?? null) ? $decoded['symbols'] : [],
            ];
        } finally {
            @fclose($sock);
        }
    }

    private function rspamcCliScan(string $rawMessage): ?array
    {
        $binary = null;
        foreach (['/usr/bin/rspamc', '/usr/local/bin/rspamc'] as $path) {
            if (is_file($path) && is_executable($path)) {
                $binary = $path;
                break;
            }
        }
        if ($binary === null) {
            return null;
        }

        $proc = @proc_open(
            [$binary, '--json'],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes
        );
        if (!is_resource($proc)) {
            return null;
        }

        @fwrite($pipes[0], $rawMessage);
        @fclose($pipes[0]);
        $stdout = (string)stream_get_contents($pipes[1]);
        @fclose($pipes[1]);
        @fclose($pipes[2]);
        $code = proc_close($proc);

        if ($code !== 0 || trim($stdout) === '') {
            return null;
        }

        $decoded = json_decode(trim($stdout), true);
        if (!is_array($decoded)) {
            return null;
        }

        return [
            'engine' => 'Rspamd',
            'driver' => 'rspamc_cli',
            'score' => round((float)($decoded['score'] ?? 0.0), 2),
            'required_score' => round((float)($decoded['required_score'] ?? $this->spamThreshold), 2),
            'action' => strtolower((string)($decoded['action'] ?? 'no action')),
            'symbols' => is_array($decoded['symbols'] ?? null) ? $decoded['symbols'] : [],
        ];
    }

    private function rspamdHeuristicFallback(string $subject, string $textBody, string $htmlBody, array $signals): array
    {
        $symbols = [];
        $addSymbol = static function (string $name, float $score, string $description) use (&$symbols): void {
            $symbols[$name] = [
                'name' => $name,
                'score' => $score,
                'description' => $description,
            ];
        };

        if (!empty($signals['eicar']) || !empty($signals['clam_virus'])) {
            $addSymbol('MALWARE_EICAR', 15.0, 'Antivirus malware signature detected');
        }
        if (!empty($signals['dangerous_attachment'])) {
            $addSymbol('EXECUTABLE_ATTACHMENT', 10.0, 'Hazardous executable attachment detected');
        }
        if (!empty($signals['dropper_cmd'])) {
            $addSymbol('DROPPER_SHELL_COMMAND', 10.0, 'Obfuscated shell/dropper indicator detected');
        }
        if (!empty($signals['script_tag'])) {
            $addSymbol('HTML_SCRIPT_TAG', 8.0, 'Active HTML script tag detected');
        }
        if (!empty($signals['malicious_uri'])) {
            $addSymbol('MALICIOUS_URI_SCHEME', 8.0, 'Dangerous javascript/vbscript/data URI detected');
        }
        if (!empty($signals['event_handler'])) {
            $addSymbol('HTML_EVENT_HANDLER', 6.5, 'Inline HTML event handler detected');
        }
        if (!empty($signals['active_container'])) {
            $addSymbol('HTML_ACTIVE_CONTAINER', 4.0, 'Active iframe/object/embed container tag');
        }

        $combinedText = $subject . "\n" . $textBody . "\n" . strip_tags($htmlBody);
        if (preg_match('/\b(?:urgent|immediately|account suspended|verify your account|confirm your password|reset your password|wire transfer|unusual sign-in|click below to avoid suspension)\b/i', $combinedText)) {
            $addSymbol('PHISHING_URGENT_CREDENTIAL', 4.5, 'Urgent credential or financial lure pattern');
        }

        if ($htmlBody !== '' && $this->hasMismatchedAnchorDomains($htmlBody)) {
            $addSymbol('SUSPICIOUS_LINK_MISMATCH', 4.0, 'Link display domain mismatches href target domain');
        }

        $alphaSubject = preg_replace('/[^A-Za-z]/', '', $subject) ?? '';
        if (strlen($alphaSubject) >= 12) {
            $upperCount = strlen(preg_replace('/[^A-Z]/', '', $alphaSubject) ?? '');
            if (($upperCount / strlen($alphaSubject)) > 0.8) {
                $addSymbol('UPPERCASE_SUBJECT', 2.0, 'Subject consists predominantly of uppercase letters');
            }
        }

        $score = 0.0;
        foreach ($symbols as $sym) {
            $score += (float)$sym['score'];
        }
        $score = round($score, 2);

        $action = 'no action';
        if ($score >= $this->rejectThreshold) {
            $action = 'reject';
        } elseif ($score >= $this->spamThreshold) {
            $action = 'add header';
        }

        return [
            'engine' => 'Rspamd',
            'driver' => 'heuristic_fallback',
            'score' => $score,
            'required_score' => round($this->spamThreshold, 2),
            'action' => $action,
            'symbols' => $symbols,
        ];
    }

    private function hasMismatchedAnchorDomains(string $html): bool
    {
        if (!preg_match_all('/<a\b[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is', $html, $matches, PREG_SET_ORDER)) {
            return false;
        }

        foreach ($matches as $m) {
            $hrefHost = strtolower((string)(parse_url(trim($m[1]), PHP_URL_HOST) ?? ''));
            if ($hrefHost === '') {
                continue;
            }
            $labelText = trim(strip_tags($m[2]));
            if (preg_match('/https?:\/\/([a-z0-9.-]+\.[a-z]{2,})/i', $labelText, $labelMatch)) {
                $labelHost = strtolower($labelMatch[1]);
                if ($labelHost !== '' && $labelHost !== $hrefHost && !str_ends_with($hrefHost, '.' . $labelHost)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function buildRfc5322Payload(string $subject, string $textBody, string $htmlBody = ''): string
    {
        $cleanSubject = trim((string)(preg_replace('/[\x00-\x1F\x7F]+/', ' ', $subject) ?? $subject));
        $boundary = 'nx_scan_' . bin2hex(random_bytes(8));

        $raw = "From: mailer@nexus.local\r\n"
            . "To: recipient@nexus.local\r\n"
            . "Subject: {$cleanSubject}\r\n"
            . "Date: " . gmdate('r') . "\r\n"
            . "Message-ID: <scan-" . bin2hex(random_bytes(8)) . "@nexus.local>\r\n"
            . "MIME-Version: 1.0\r\n";

        if (trim($htmlBody) !== '') {
            $raw .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n\r\n"
                . "--{$boundary}\r\n"
                . "Content-Type: text/plain; charset=UTF-8\r\n\r\n"
                . $textBody . "\r\n"
                . "--{$boundary}\r\n"
                . "Content-Type: text/html; charset=UTF-8\r\n\r\n"
                . $htmlBody . "\r\n"
                . "--{$boundary}--\r\n";
            return $raw;
        }

        return $raw . "Content-Type: text/plain; charset=UTF-8\r\n\r\n" . $textBody . "\r\n";
    }

    private function streamWriteAll($sock, string $data): bool
    {
        $total = strlen($data);
        $written = 0;
        while ($written < $total) {
            $bytes = @fwrite($sock, substr($data, $written));
            if ($bytes === false || $bytes === 0) {
                return false;
            }
            $written += $bytes;
        }
        return true;
    }

    private function decodeChunkedHttpBody(string $body): string
    {
        $decoded = '';
        $pos = 0;
        $len = strlen($body);
        while ($pos < $len) {
            $lineEnd = strpos($body, "\n", $pos);
            if ($lineEnd === false) {
                break;
            }
            $hexLen = trim(explode(';', substr($body, $pos, $lineEnd - $pos), 2)[0]);
            $chunkLen = hexdec($hexLen);
            if ($chunkLen <= 0) {
                break;
            }
            $pos = $lineEnd + 1;
            $decoded .= substr($body, $pos, $chunkLen);
            $pos += $chunkLen + 2;
        }
        return $decoded !== '' ? $decoded : $body;
    }

    /**
     * Recursively normalize MongoDB BSON / stdClass structures into plain PHP arrays.
     */
    public static function normalizeAttachments(mixed $attachments): array
    {
        if (is_object($attachments)) {
            $attachments = $attachments instanceof \Traversable
                ? iterator_to_array($attachments)
                : (array)$attachments;
        }
        if (!is_array($attachments)) {
            return [];
        }

        $normalized = [];
        foreach ($attachments as $item) {
            if (is_object($item)) {
                $item = $item instanceof \Traversable
                    ? iterator_to_array($item)
                    : (array)$item;
            }
            if (is_array($item)) {
                $normalized[] = self::normalizeValueRecursive($item);
            } elseif (is_scalar($item)) {
                $normalized[] = (string)$item;
            }
        }

        return $normalized;
    }

    private static function normalizeValueRecursive(mixed $value): mixed
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
            $value[$k] = self::normalizeValueRecursive($v);
        }
        return $value;
    }

    private function decodeEntitiesIteratively(string $value): string
    {
        $decoded = $value;
        for ($i = 0; $i < 3; $i++) {
            $next = html_entity_decode($decoded, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if ($next === $decoded) {
                break;
            }
            $decoded = $next;
        }
        return $decoded;
    }

    /**
     * Strictly sanitize email HTML via DOMDocument allowlist so no script or active tag survives.
     */
    public function sanitizeHtml(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        // Strip caller-supplied DOCTYPE/ENTITY declarations before DOM parsing
        $cleanInput = preg_replace('/<!(?:DOCTYPE|ENTITY)[^>]*>/i', '', $html) ?? $html;

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $prevUseErrors = libxml_use_internal_errors(true);

        $wrapped = '<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body><div id="nx-mail-root">'
            . $cleanInput
            . '</div></body></html>';

        $dom->loadHTML($wrapped, LIBXML_NONET | LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($prevUseErrors);

        $root = $dom->getElementById('nx-mail-root');
        if ($root === null) {
            return '<p>' . htmlspecialchars(strip_tags($cleanInput), ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</p>';
        }

        $this->sanitizeNodeRecursive($root);

        $output = '';
        foreach ($root->childNodes as $child) {
            $output .= $dom->saveHTML($child);
        }

        return $output !== '' ? $output : '<p>(Empty message body)</p>';
    }

    private function sanitizeNodeRecursive(\DOMNode $node): void
    {
        $children = [];
        foreach ($node->childNodes as $child) {
            $children[] = $child;
        }

        $dropElements = [
            'script', 'style', 'iframe', 'frame', 'frameset', 'object', 'embed',
            'applet', 'form', 'input', 'button', 'textarea', 'select', 'svg',
            'math', 'base', 'meta', 'link', 'noscript', 'template', 'audio',
            'video', 'source', 'track', 'canvas',
        ];

        foreach ($children as $child) {
            if ($child instanceof \DOMComment || $child instanceof \DOMDocumentType || $child instanceof \DOMProcessingInstruction) {
                $node->removeChild($child);
                continue;
            }

            if ($child instanceof \DOMElement) {
                $tag = strtolower($child->tagName);
                if (!in_array($tag, self::ALLOWED_HTML_ELEMENTS, true)) {
                    // Drop dangerous container elements and their script payloads outright
                    if (in_array($tag, $dropElements, true)) {
                        $node->removeChild($child);
                        continue;
                    }
                    // Unwrap benign unknown formatting tags by stripping the tag wrapper safely
                    $this->sanitizeNodeRecursive($child);
                    while ($child->firstChild !== null) {
                        $node->insertBefore($child->firstChild, $child);
                    }
                    $node->removeChild($child);
                    continue;
                }

                $attrs = [];
                foreach ($child->attributes as $attr) {
                    $attrs[] = $attr->name;
                }
                foreach ($attrs as $attrName) {
                    $lower = strtolower($attrName);
                    if (!in_array($lower, self::ALLOWED_ATTRIBUTES, true)) {
                        $child->removeAttribute($attrName);
                        continue;
                    }

                    if ($lower === 'colspan' || $lower === 'rowspan') {
                        $spanVal = trim($child->getAttribute($attrName));
                        if (!ctype_digit($spanVal) || (int)$spanVal < 1 || (int)$spanVal > 20) {
                            $child->removeAttribute($attrName);
                        }
                        continue;
                    }

                    if ($lower === 'href') {
                        $decodedUrl = $this->decodeEntitiesIteratively(trim($child->getAttribute($attrName)));
                        $normalizedUrl = preg_replace('/[\x00-\x20]+/', '', $decodedUrl) ?? $decodedUrl;
                        $scheme = parse_url($normalizedUrl, PHP_URL_SCHEME);
                        $hasProtocolRelative = str_starts_with($normalizedUrl, '//');
                        if (
                            $normalizedUrl === ''
                            || $hasProtocolRelative
                            || ($scheme !== null && !in_array(strtolower((string)$scheme), ['http', 'https', 'mailto'], true))
                        ) {
                            $child->removeAttribute($attrName);
                        }
                    }
                }

                if ($tag === 'a') {
                    $child->setAttribute('rel', 'noopener noreferrer');
                    $child->setAttribute('target', '_blank');
                }
            }

            if ($child->hasChildNodes()) {
                $this->sanitizeNodeRecursive($child);
            }
        }
    }

    private function scanRawWithClamIfInstalled(string $content, ?string $binary = null): ?string
    {
        $binary = $binary ?? $this->findClamScanBinary();
        if ($binary === null) {
            return null;
        }

        $tmpFile = tempnam(sys_get_temp_dir(), 'nxav_');
        if ($tmpFile === false) {
            return null;
        }

        try {
            file_put_contents($tmpFile, $content);
            return $this->runClamScan($tmpFile, $binary);
        } finally {
            @unlink($tmpFile);
        }
    }

    private function runClamScan(string $filePath, ?string $binary = null): ?string
    {
        $binary = $binary ?? $this->findClamScanBinary();
        if ($binary === null || !file_exists($filePath)) {
            return null;
        }

        $proc = @proc_open(
            [$binary, '--no-summary', '--infected', $filePath],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes
        );
        if (!is_resource($proc)) {
            return null;
        }

        @fclose($pipes[0]);
        $stdout = (string)stream_get_contents($pipes[1]);
        @fclose($pipes[1]);
        @fclose($pipes[2]);
        $code = proc_close($proc);

        if ($code === 1 && trim($stdout) !== '') {
            return trim($stdout);
        }

        return null;
    }

    private function findClamScanBinary(): ?string
    {
        foreach (['/usr/bin/clamdscan', '/usr/bin/clamscan', '/usr/local/bin/clamscan'] as $path) {
            if (is_file($path) && is_executable($path)) {
                return $path;
            }
        }
        return null;
    }
}
