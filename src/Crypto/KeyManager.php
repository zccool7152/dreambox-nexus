<?php
declare(strict_types=1);

namespace Nexus\Crypto;

use RuntimeException;

/**
 * Enterprise Key Management & Envelope Encryption Engine
 */
class KeyManager
{
    private Crypto $masterCrypto;

    public function __construct(string $masterKey)
    {
        $this->masterCrypto = new Crypto($masterKey);
    }

    /**
     * Generate a new cryptographically random Data Encryption Key (DEK)
     */
    public function generateDataKey(): array
    {
        $rawDek = random_bytes(32);
        $encryptedDek = $this->masterCrypto->encrypt($rawDek);

        return [
            'plaintext_key' => $rawDek,
            'encrypted_key' => $encryptedDek,
        ];
    }

    /**
     * Decrypt a Data Encryption Key (DEK) using the Master Key
     */
    public function decryptDataKey(string $encryptedDek): string
    {
        return $this->masterCrypto->decrypt($encryptedDek);
    }

    /**
     * Rotate an existing DEK under a new Master Key
     */
    public function reEncryptDataKey(string $encryptedDek, KeyManager $newKeyManager): string
    {
        $rawKey = $this->decryptDataKey($encryptedDek);
        return $newKeyManager->masterCrypto->encrypt($rawKey);
    }
}

