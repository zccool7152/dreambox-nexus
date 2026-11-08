<?php
declare(strict_types=1);

namespace Nexus\Security;

/**
 * Recursive Input Sanitizer
 */
class Sanitizer
{
    public static function clean(mixed $data): mixed
    {
        if (is_array($data)) {
            $cleaned = [];
            foreach ($data as $key => $val) {
                $cleanKey = is_string($key) ? self::cleanString($key) : $key;
                $cleaned[$cleanKey] = self::clean($val);
            }
            return $cleaned;
        }

        if (is_string($data)) {
            return self::cleanString($data);
        }

        return $data;
    }

    public static function cleanString(string $value): string
    {
        // Strip null bytes
        $value = str_replace(chr(0), '', $value);
        return trim(htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
    }
}

