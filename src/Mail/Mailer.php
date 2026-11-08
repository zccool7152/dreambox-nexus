<?php
declare(strict_types=1);

namespace Nexus\Mail;

use RuntimeException;
use InvalidArgumentException;

/**
 * Hardened Transactional Mailer
 *
 * Implements strict CRLF injection defense to eliminate email header tampering.
 */
class Mailer
{
    private string $host;
    private int $port;
    private ?string $username;
    private ?string $password;
    private ?string $encryption;
    private string $fromAddress;
    private string $fromName;

    public function __construct(
        string $host = '127.0.0.1',
        int $port = 1025,
        ?string $username = null,
        ?string $password = null,
        ?string $encryption = null,
        string $fromAddress = 'nexus@localhost',
        string $fromName = 'Dreambox Nexus'
    ) {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->encryption = $encryption;
        $this->fromAddress = $this->sanitizeHeader($fromAddress);
        $this->fromName = $this->sanitizeHeader($fromName);
    }

    public function send(string $to, string $subject, string $htmlBody, string $textBody = ''): bool
    {
        $safeTo = $this->sanitizeHeader($to);
        $safeSubject = $this->sanitizeHeader($subject);

        if (!filter_var($safeTo, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid recipient email address: '{$safeTo}'");
        }

        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            "From: {$this->fromName} <{$this->fromAddress}>",
            "Reply-To: {$this->fromAddress}",
            'X-Mailer: Dreambox Nexus Core',
        ];

        return mail($safeTo, $safeSubject, $htmlBody, implode("\r\n", $headers));
    }

    private function sanitizeHeader(string $value): string
    {
        // Strip carriage returns and line feeds to prevent CRLF injection
        return trim(str_replace(["\r", "\n", "%0a", "%0d"], '', $value));
    }
}

