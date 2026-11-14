<?php
declare(strict_types=1);

namespace Nexus\Mail;

/**
 * Nexus Antivirus & Heuristic Threat Scanner
 *
 * Scans inbound, outbound, and opened emails for malware signatures (EICAR),
 * executable attachments, embedded scripts, and malicious URIs before rendering
 * sanitized HTML inside an isolated ephemeral Reader Jail.
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

    /**
     * Scan email content and attachments for malware/XSS/phishing threats and return sanitized HTML.
     */
    public function scan(string $subject, string $textBody, string $htmlBody = '', array $attachments = []): array
    {
        $threats = [];
        $severity = 0; // 0 = clean, 1 = suspicious, 2 = infected

        $combined = $subject . "\n" . $textBody . "\n" . $htmlBody;

        // 1. Standard EICAR Malware Signature Check
        if (str_contains($combined, self::EICAR_SIGNATURE)) {
            $threats[] = 'Malware Signature Detected: EICAR-Standard-Antivirus-Test-File';
            $severity = 2;
        }

        // 2. Active Script / Payload Injection Signatures
        if (preg_match('/<script\b/i', $combined)) {
            $threats[] = 'Executable HTML <script> Payload Stripped';
            $severity = max($severity, 2);
        }

        if (preg_match('/<\s*(iframe|object|embed|applet|base|meta)\b/i', $htmlBody)) {
            $threats[] = 'Embedded Active Container Tag (<iframe/object/embed>) Blocked';
            $severity = max($severity, 1);
        }

        if (preg_match('/\bon[a-z]+\s*=/i', $htmlBody)) {
            $threats[] = 'Inline DOM Event Handler (onload/onerror/onclick) Stripped';
            $severity = max($severity, 1);
        }

        $decodedForUriCheck = html_entity_decode($combined, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (preg_match('/(?:javascript|vbscript)\s*:|data\s*:\s*text\/html/i', $decodedForUriCheck)) {
            $threats[] = 'Malicious Script/HTML Data URI Neutralized';
            $severity = max($severity, 2);
        }

        if (preg_match('/(?:eval\s*\(\s*base64_decode|powershell\s+-e(?:nc|ncodedcommand)?|WScript\.Shell|cmd\.exe\s+\/c)/i', $combined)) {
            $threats[] = 'Obfuscated Shell/Dropper Command Indicator';
            $severity = max($severity, 2);
        }

        // 3. Attachment Extension & Subject Double-Extension Inspection
        foreach ($attachments as $att) {
            $name = is_array($att) ? (string)($att['name'] ?? '') : (string)$att;
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if ($ext !== '' && in_array($ext, self::DANGEROUS_EXTENSIONS, true)) {
                $threats[] = "Hazardous Executable/Script Attachment: {$name}";
                $severity = max($severity, 2);
            }
            if (is_array($att) && !empty($att['tmp_path']) && is_string($att['tmp_path'])) {
                $clamResult = $this->runClamScan($att['tmp_path']);
                if ($clamResult !== null) {
                    $threats[] = "ClamAV Signature Match: {$clamResult}";
                    $severity = 2;
                }
            }
        }

        if (preg_match('/\b[a-z0-9._-]+\.(?:exe|scr|bat|ps1|vbs|hta)\b/i', $subject, $subjMatch)) {
            $threats[] = "Suspicious Executable Filename Referenced: {$subjMatch[0]}";
            $severity = max($severity, 1);
        }

        // 4. Optional ClamAV body signature scan if daemon/binary is installed on host
        $clamBodyHit = $this->scanRawWithClamIfInstalled($combined);
        if ($clamBodyHit !== null) {
            $threats[] = "ClamAV Malware Match: {$clamBodyHit}";
            $severity = 2;
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
            'scanner' => 'Nexus AV + Heuristic Engine',
            'scanned_at' => gmdate('Y-m-d H:i:s') . ' UTC',
            'sanitized_html' => $this->sanitizeHtml($sourceHtml),
        ];
    }

    /**
     * Strictly sanitize email HTML via DOMDocument allowlist so no script or active tag survives.
     */
    public function sanitizeHtml(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $prevUseErrors = libxml_use_internal_errors(true);

        $wrapped = '<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body><div id="nx-mail-root">'
            . $html
            . '</div></body></html>';

        $dom->loadHTML($wrapped, LIBXML_NONET | LIBXML_NOENT | LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($prevUseErrors);

        $root = $dom->getElementById('nx-mail-root');
        if ($root === null) {
            return '<p>' . htmlspecialchars(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</p>';
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

        foreach ($children as $child) {
            if ($child instanceof \DOMComment) {
                $node->removeChild($child);
                continue;
            }

            if ($child instanceof \DOMElement) {
                $tag = strtolower($child->tagName);
                if (!in_array($tag, self::ALLOWED_HTML_ELEMENTS, true)) {
                    // Drop dangerous container elements and their script payloads outright
                    if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'applet', 'form', 'svg', 'noscript'], true)) {
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

                    if ($lower === 'href') {
                        $val = html_entity_decode(
                            html_entity_decode(trim($child->getAttribute($attrName)), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                            ENT_QUOTES | ENT_HTML5,
                            'UTF-8'
                        );
                        $scheme = parse_url($val, PHP_URL_SCHEME);
                        if ($scheme !== null && !in_array(strtolower((string)$scheme), ['http', 'https', 'mailto'], true)) {
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

    private function scanRawWithClamIfInstalled(string $content): ?string
    {
        $binary = $this->findClamScanBinary();
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
