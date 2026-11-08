<?php
declare(strict_types=1);

namespace Nexus\Crypto;

/**
 * Hardened Password Hashing Engine
 *
 * Enforces Argon2id hashing algorithms with automated rehash checks.
 */
class Hasher
{
    private array $options;

    public function __construct(array $options = [])
    {
        $this->options = $options ?: [
            'memory_cost' => PASSWORD_ARGON2_DEFAULT_MEMORY_COST,
            'time_cost' => PASSWORD_ARGON2_DEFAULT_TIME_COST,
            'threads' => PASSWORD_ARGON2_DEFAULT_THREADS,
        ];
    }

    public function hash(string $value): string
    {
        $hash = password_hash($value, PASSWORD_ARGON2ID, $this->options);
        if ($hash === false) {
            // Fallback to bcrypt if Argon2id is unsupported in environment
            $hash = password_hash($value, PASSWORD_BCRYPT, ['cost' => 12]);
        }
        return $hash;
    }

    public function verify(string $value, string $hashedValue): bool
    {
        return password_verify($value, $hashedValue);
    }

    public function needsRehash(string $hashedValue): bool
    {
        return password_needs_rehash($hashedValue, PASSWORD_ARGON2ID, $this->options);
    }
}

