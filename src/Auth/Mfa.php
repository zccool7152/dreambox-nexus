<?php
declare(strict_types=1);

namespace Nexus\Auth;

use InvalidArgumentException;

/**
 * Pure-PHP RFC 6238 TOTP Multi-Factor Authentication Engine
 */
class Mfa
{
    private const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public function generateSecret(int $length = 16): string
    {
        $secret = '';
        $bytes = random_bytes($length);
        for ($i = 0; $i < $length; $i++) {
            $secret .= self::BASE32_ALPHABET[ord($bytes[$i]) % 32];
        }
        return $secret;
    }

    public function getOtp(string $secret, ?int $timestamp = null): string
    {
        $timeSlice = floor(($timestamp ?? time()) / 30);
        $binaryTime = pack('N*', 0) . pack('N*', $timeSlice);

        $binaryKey = $this->base32Decode($secret);
        $hash = hash_hmac('sha1', $binaryTime, $binaryKey, true);

        $offset = ord($hash[19]) & 0xf;
        $otp = (
            ((ord($hash[$offset]) & 0x7f) << 24) |
            ((ord($hash[$offset + 1]) & 0xff) << 16) |
            ((ord($hash[$offset + 2]) & 0xff) << 8) |
            (ord($hash[$offset + 3]) & 0xff)
        ) % 1000000;

        return str_pad((string)$otp, 6, '0', STR_PAD_LEFT);
    }

    public function verifyCode(string $secret, string $code, int $discrepancy = 1): bool
    {
        $currentTime = time();
        for ($i = -$discrepancy; $i <= $discrepancy; $i++) {
            $calculatedOtp = $this->getOtp($secret, $currentTime + ($i * 30));
            if (hash_equals($calculatedOtp, trim($code))) {
                return true;
            }
        }
        return false;
    }

    public function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = bin2hex(random_bytes(4)) . '-' . bin2hex(random_bytes(4));
        }
        return $codes;
    }

    private function base32Decode(string $b32): string
    {
        $b32 = strtoupper(trim($b32));
        $buffer = 0;
        $bitsLeft = 0;
        $binary = '';

        for ($i = 0; $i < strlen($b32); $i++) {
            $val = strpos(self::BASE32_ALPHABET, $b32[$i]);
            if ($val === false) {
                continue;
            }

            $buffer = ($buffer << 5) | $val;
            $bitsLeft += 5;

            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $binary .= chr(($buffer >> $bitsLeft) & 0xff);
            }
        }

        return $binary;
    }
}

