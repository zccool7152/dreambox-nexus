<?php
declare(strict_types=1);

namespace Nexus\Storage;

use RuntimeException;

/**
 * Filesystem Storage Abstraction
 */
class Storage
{
    private string $privatePath;
    private string $publicPath;

    public function __construct(?string $baseStoragePath = null)
    {
        $base = $baseStoragePath ?? dirname(__DIR__, 2) . '/storage/app';
        $this->privatePath = $base . '/private';
        $this->publicPath = $base . '/public';

        if (!is_dir($this->privatePath)) {
            mkdir($this->privatePath, 0700, true);
        }
        if (!is_dir($this->publicPath)) {
            mkdir($this->publicPath, 0755, true);
        }
    }

    public function put(string $path, string $contents, bool $isPublic = false): string
    {
        $targetDir = $isPublic ? $this->publicPath : $this->privatePath;
        $fullPath = $targetDir . '/' . ltrim($path, '/');

        $dir = dirname($fullPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (file_put_contents($fullPath, $contents) === false) {
            throw new RuntimeException("Failed to write file to storage: '{$fullPath}'");
        }

        return $fullPath;
    }

    public function get(string $path, bool $isPublic = false): ?string
    {
        $targetDir = $isPublic ? $this->publicPath : $this->privatePath;
        $fullPath = $targetDir . '/' . ltrim($path, '/');

        if (!file_exists($fullPath)) {
            return null;
        }

        return file_get_contents($fullPath) ?: null;
    }

    public function delete(string $path, bool $isPublic = false): bool
    {
        $targetDir = $isPublic ? $this->publicPath : $this->privatePath;
        $fullPath = $targetDir . '/' . ltrim($path, '/');

        return file_exists($fullPath) ? unlink($fullPath) : false;
    }

    public function getPrivatePath(): string
    {
        return $this->privatePath;
    }
}

