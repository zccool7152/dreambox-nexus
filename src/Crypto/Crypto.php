<?php
declare(strict_types=1);

namespace Nexus\Crypto;

use RuntimeException;
use InvalidArgumentException;

/**
 * Authenticated Encryption with Associated Data (AEAD) Engine
 *
 * Enforces AES-256-GCM with cryptographic authentication tags
 * to eliminate bit-flipping and padding-oracle attacks.
 */
class Crypto
{
    private const CIPHER = 'aes-256-gcm';
    private const IV_LENGTH = 12; // 96-bit IV recommended for GCM
    private const TAG_LENGTH = 16; // 128-bit authentication tag

    private string $key;

    public function __construct(string $key)
    {
        // Decode base64 key if prefixed
        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7), true);
        }

        if ($key === false || strlen($key) < 32) {
            throw new InvalidArgumentException("Encryption key must be at least 32 bytes (256 bits).");
        }

        $this->key = substr($key, 0, 32);
    }

    public function encrypt(string $plaintext, string $associatedData = ''): string
    {
        $iv = random_bytes(self::IV_LENGTH);
        $tag = '';

        $ciphertext = openssl_encrypt(
            $plaintext,
            self::CIPHER,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            $associatedData,
            self::TAG_LENGTH
        );

        if ($ciphertext === false) {
            throw new RuntimeException("AEAD encryption operation failed.");
        }

        // Pack: IV (12 bytes) + Tag (16 bytes) + Ciphertext
        return base64_encode($iv . $tag . $ciphertext);
    }

    public function decrypt(string $payload, string $associatedData = ''): string
    {
        $raw = base64_decode($payload, true);
        if ($raw === false || strlen($raw) < (self::IV_LENGTH + self::TAG_LENGTH)) {
            throw new InvalidArgumentException("Malformed ciphertext payload.");
        }

        $iv = substr($raw, 0, self::IV_LENGTH);
        $tag = substr($raw, self::IV_LENGTH, self::TAG_LENGTH);
        $ciphertext = substr($raw, self::IV_LENGTH + self::TAG_LENGTH);

        $plaintext = openssl_decrypt(
            $ciphertext,
            self::CIPHER,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            $associatedData
        );

        if ($plaintext === false) {
            throw new RuntimeException("AEAD decryption failed: Authentication tag verification rejected (tampered ciphertext).");
        }

        return $plaintext;
    }
}

