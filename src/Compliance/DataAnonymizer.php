<?php
declare(strict_types=1);

namespace Nexus\Compliance;

/**
 * Cryptographic Pseudonymization & Data Anonymization Engine
 */
class DataAnonymizer
{
    private string $salt;

    public function __construct(string $salt = 'nexus_gdpr_salt')
    {
        $this->salt = $salt;
    }

    public function anonymizeString(string $value): string
    {
        return 'ANON-' . substr(hash('sha256', $value . $this->salt), 0, 16);
    }

    public function anonymizeEmail(string $email): string
    {
        $hash = substr(hash('sha256', $email . $this->salt), 0, 12);
        return "anon_{$hash}@anonymous.local";
    }

    public function anonymizeIp(string $ip): string
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            return "{$parts[0]}.{$parts[1]}.0.0";
        }
        return '::ffff:0.0.0.0';
    }
}

