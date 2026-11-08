<?php
declare(strict_types=1);

namespace Nexus\Core;

/**
 * Zero-Dependency Environment Parser
 *
 * Parses .env files safely with type casting, quoted values, and immutability.
 */
class Env
{
    private static array $repository = [];
    private static bool $loaded = false;

    public static function load(string $filePath): void
    {
        if (!file_exists($filePath)) {
            return;
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (!str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Strip enclosing quotes
            if (
                (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))
            ) {
                $value = substr($value, 1, -1);
            }

            // Strip trailing inline comment if not quoted
            if (str_contains($value, ' #')) {
                $value = trim(explode(' #', $value, 2)[0]);
            }

            $castValue = match (strtolower($value)) {
                'true', '(true)' => true,
                'false', '(false)' => false,
                'null', '(null)', 'empty' => null,
                default => is_numeric($value) ? (str_contains($value, '.') ? (float)$value : (int)$value) : $value,
            };

            self::$repository[$key] = $castValue;

            if (!isset($_SERVER[$key])) {
                $_SERVER[$key] = $castValue;
            }
            if (!isset($_ENV[$key])) {
                $_ENV[$key] = $castValue;
            }
            putenv("{$key}={$value}");
        }

        self::$loaded = true;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, self::$repository)) {
            return self::$repository[$key];
        }

        $val = getenv($key);
        if ($val !== false) {
            return match (strtolower((string)$val)) {
                'true' => true,
                'false' => false,
                'null' => null,
                default => $val,
            };
        }

        return $default;
    }

    public static function isLoaded(): bool
    {
        return self::$loaded;
    }
}

