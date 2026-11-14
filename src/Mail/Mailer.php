<?php
declare(strict_types=1);

namespace Nexus\Mail;

use RuntimeException;
use InvalidArgumentException;

/**
 * Hardened Transactional SMTP Mailer
 *
 * Pure-PHP SMTP socket client supporting TLS/STARTTLS, AUTH LOGIN,
 * RFC 5321 dot-stuffing, and strict CRLF header injection defense.
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

        if ($this->host === '' || $this->port <= 0) {
            throw new RuntimeException('SMTP host and port must be configured.');
        }

        $headers = [
            'MIME-Version: 1.0',
            "From: {$this->fromName} <{$this->fromAddress}>",
            "Reply-To: {$this->fromAddress}",
            'X-Mailer: Dreambox Nexus Core',
        ];

        if ($textBody !== '') {
            $boundary = 'nexus_' . bin2hex(random_bytes(12));
            $headers[] = "Content-Type: multipart/alternative; boundary=\"{$boundary}\"";
            $mimeBody = "--{$boundary}\r\n"
                . "Content-Type: text/plain; charset=UTF-8\r\n"
                . "Content-Transfer-Encoding: 8bit\r\n\r\n"
                . $textBody . "\r\n\r\n"
                . "--{$boundary}\r\n"
                . "Content-Type: text/html; charset=UTF-8\r\n"
                . "Content-Transfer-Encoding: 8bit\r\n\r\n"
                . $htmlBody . "\r\n\r\n"
                . "--{$boundary}--";
        } else {
            $headers[] = 'Content-Type: text/html; charset=UTF-8';
            $mimeBody = $htmlBody;
        }

        return $this->sendSmtp($safeTo, $safeSubject, $mimeBody, $headers);
    }

    /**
     * Transmit mail over raw TCP/SMTP with STARTTLS (TLS 1.2/1.3) and AUTH LOGIN support.
     */
    private function sendSmtp(string $to, string $subject, string $htmlBody, array $headers): bool
    {
        $remote = ($this->encryption === 'ssl' ? 'ssl://' : 'tcp://') . $this->host . ':' . $this->port;
        $socket = @stream_socket_client($remote, $errno, $errstr, 3);
        if ($socket === false) {
            return false;
        }

        stream_set_timeout($socket, 3);

        try {
            if (!$this->expectSmtpCode($socket, 220)) {
                return false;
            }

            if (!$this->smtpCommand($socket, 'EHLO localhost', 250)) {
                return false;
            }

            $enc = strtolower((string)($this->encryption ?? ''));
            if (in_array($enc, ['tls', 'starttls'], true)) {
                if (!$this->smtpCommand($socket, 'STARTTLS', 220)) {
                    return false;
                }
                $cryptoMethod = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
                if (!@stream_socket_enable_crypto($socket, true, $cryptoMethod)) {
                    return false;
                }
                if (!$this->smtpCommand($socket, 'EHLO localhost', 250)) {
                    return false;
                }
            }

            if ($this->username !== null && $this->username !== '' && strtolower($this->username) !== 'null') {
                if (!$this->smtpCommand($socket, 'AUTH LOGIN', 334)) {
                    return false;
                }
                if (!$this->smtpCommand($socket, base64_encode($this->username), 334)) {
                    return false;
                }
                if (!$this->smtpCommand($socket, base64_encode((string)$this->password), 235)) {
                    return false;
                }
            }

            if (!$this->smtpCommand($socket, "MAIL FROM:<{$this->fromAddress}>", 250)) {
                return false;
            }
            if (!$this->smtpCommand($socket, "RCPT TO:<{$to}>", 250)) {
                return false;
            }
            if (!$this->smtpCommand($socket, 'DATA', 354)) {
                return false;
            }

            $allHeaders = array_merge([
                "To: <{$to}>",
                "Subject: {$subject}",
            ], $headers);

            // Dot-stuff body lines per RFC 5321 Section 4.5.2
            $normalizedBody = str_replace(["\r\n", "\r"], "\n", $htmlBody);
            $dotStuffed = preg_replace('/^\./m', '..', $normalizedBody) ?? $normalizedBody;
            $wireBody = str_replace("\n", "\r\n", $dotStuffed);

            $message = implode("\r\n", $allHeaders) . "\r\n\r\n" . $wireBody . "\r\n.";
            if (!$this->smtpCommand($socket, $message, 250)) {
                return false;
            }

            $this->smtpCommand($socket, 'QUIT', 221);
            return true;
        } finally {
            @fclose($socket);
        }
    }

    private function smtpCommand(mixed $socket, string $command, int $expectedCode): bool
    {
        fwrite($socket, $command . "\r\n");
        return $this->expectSmtpCode($socket, $expectedCode);
    }

    private function expectSmtpCode(mixed $socket, int $expectedCode): bool
    {
        $response = '';
        while (($line = fgets($socket, 515)) !== false) {
            $response .= $line;
            if (strlen($line) < 4 || $line[3] === ' ') {
                break;
            }
        }
        if ($response === '') {
            return false;
        }
        return (int)substr($response, 0, 3) === $expectedCode;
    }

    private function sanitizeHeader(string $value): string
    {
        // Strip carriage returns and line feeds to prevent CRLF injection
        return trim(str_replace(["\r", "\n", "%0a", "%0d"], '', $value));
    }
}

