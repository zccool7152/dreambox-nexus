<?php
declare(strict_types=1);

namespace Nexus\Security;

/**
 * Compliance Data Masker
 *
 * Enforces automated masking of Payment Card PANs (PCI-DSS Req 3.4),
 * Social Security Numbers, and patient ePHI identifiers (HIPAA).
 */
class DataMasker
{
    /**
     * Mask a 13-19 digit Primary Account Number (PAN) to first-6 and last-4
     */
    public static function maskPan(string $pan): string
    {
        $clean = preg_replace('/\D/', '', $pan) ?? '';
        $len = strlen($clean);

        if ($len < 12) {
            return str_repeat('*', $len);
        }

        $first6 = substr($clean, 0, 6);
        $last4 = substr($clean, -4);
        $maskedLength = $len - 10;

        return $first6 . str_repeat('*', $maskedLength) . $last4;
    }

    /**
     * Mask a 9-digit Social Security Number (SSN) to last-4
     */
    public static function maskSsn(string $ssn): string
    {
        $clean = preg_replace('/\D/', '', $ssn) ?? '';
        if (strlen($clean) !== 9) {
            return '***-**-****';
        }

        return '***-**-' . substr($clean, -4);
    }

    /**
     * Mask an email address to reveal only first and last characters of local part
     */
    public static function maskEmail(string $email): string
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return '***@***.***';
        }

        [$name, $domain] = explode('@', $email, 2);
        $len = strlen($name);

        if ($len <= 2) {
            $maskedName = $name[0] . '*';
        } else {
            $maskedName = $name[0] . str_repeat('*', $len - 2) . $name[$len - 1];
        }

        return $maskedName . '@' . $domain;
    }

    /**
     * Recursively mask sensitive keys in an array before logging or emission
     */
    public static function maskArray(array $data): array
    {
        $masked = [];
        foreach ($data as $key => $value) {
            $lowerKey = strtolower((string)$key);

            if (is_array($value)) {
                $masked[$key] = self::maskArray($value);
                continue;
            }

            if (!is_string($value)) {
                $masked[$key] = $value;
                continue;
            }

            if (str_contains($lowerKey, 'card') || str_contains($lowerKey, 'pan')) {
                $masked[$key] = self::maskPan($value);
            } elseif (str_contains($lowerKey, 'ssn')) {
                $masked[$key] = self::maskSsn($value);
            } elseif (str_contains($lowerKey, 'email')) {
                $masked[$key] = self::maskEmail($value);
            } elseif (in_array($lowerKey, ['password', 'secret', 'token', 'cvv', 'cvc', 'pin', 'key'], true)) {
                $masked[$key] = '[REDACTED]';
            } else {
                $masked[$key] = $value;
            }
        }

        return $masked;
    }
}

