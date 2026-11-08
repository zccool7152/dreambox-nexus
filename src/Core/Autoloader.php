<?php
declare(strict_types=1);

namespace Nexus\Core;

/**
 * Hardened PSR-4 Autoloader
 *
 * Implements strict namespace containment, regex-constrained identifier checks,
 * and realpath directory traversal immunity.
 */
class Autoloader
{
    private string $baseDir;
    private array $prefixes = [];

    public function __construct(string $baseDir)
    {
        $this->baseDir = rtrim($baseDir, '/\\');
    }

    public function addNamespace(string $prefix, string $baseDirectory): void
    {
        $prefix = trim($prefix, '\\') . '\\';
        $baseDirectory = rtrim($baseDirectory, '/\\') . DIRECTORY_SEPARATOR;
        $this->prefixes[$prefix] = $baseDirectory;
    }

    public function register(): void
    {
        spl_autoload_register([$this, 'loadClass']);
    }

    public function loadClass(string $class): bool
    {
        // Reject null bytes and illegal characters (LFI defense)
        if (!preg_match('/^[a-zA-Z0-9_\\\\]+$/', $class)) {
            return false;
        }

        foreach ($this->prefixes as $prefix => $baseDirectory) {
            if (!str_starts_with($class, $prefix)) {
                continue;
            }

            $relativeClass = substr($class, strlen($prefix));
            $file = $baseDirectory . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

            if (file_exists($file)) {
                // Ensure resolved realpath does not escape the configured directory root
                $realBase = realpath($baseDirectory);
                $realFile = realpath($file);

                if ($realBase !== false && $realFile !== false && str_starts_with($realFile, $realBase)) {
                    require $file;
                    return true;
                }
            }
        }

        return false;
    }
}
