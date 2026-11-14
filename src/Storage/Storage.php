<?php
declare(strict_types=1);

namespace Nexus\Storage;

use InvalidArgumentException;
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

    /**
     * Resolve and strictly confine a storage path inside its base directory.
     */
    private function resolveSafePath(string $path, bool $isPublic): string
    {
        if (str_contains($path, "\0") || trim($path) === '') {
            throw new InvalidArgumentException("Invalid storage path specified.");
        }

        $normalized = str_replace('\\', '/', $path);
        $rawSegments = explode('/', ltrim($normalized, '/'));
        $cleanSegments = [];

        foreach ($rawSegments as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                throw new InvalidArgumentException("Directory traversal detected in storage path: '{$path}'");
            }
            $cleanSegments[] = $segment;
        }

        if (empty($cleanSegments)) {
            throw new InvalidArgumentException("Invalid storage path specified.");
        }

        $targetDir = $isPublic ? $this->publicPath : $this->privatePath;
        $baseReal = realpath($targetDir);
        if ($baseReal === false) {
            throw new RuntimeException("Storage base directory could not be resolved.");
        }

        $relativePath = implode(DIRECTORY_SEPARATOR, $cleanSegments);
        $fullPath = $baseReal . DIRECTORY_SEPARATOR . $relativePath;

        $parentDir = dirname($fullPath);
        if (is_dir($parentDir)) {
            $parentReal = realpath($parentDir);
            if ($parentReal === false || ($parentReal !== $baseReal && !str_starts_with($parentReal, $baseReal . DIRECTORY_SEPARATOR))) {
                throw new InvalidArgumentException("Storage path escapes root boundary: '{$path}'");
            }
        }

        if (file_exists($fullPath) || is_link($fullPath)) {
            $realTarget = realpath($fullPath);
            if ($realTarget === false || !str_starts_with($realTarget, $baseReal . DIRECTORY_SEPARATOR)) {
                throw new InvalidArgumentException("Storage target escapes root boundary: '{$path}'");
            }
        }

        return $fullPath;
    }

    public function put(string $path, string $contents, bool $isPublic = false): string
    {
        $fullPath = $this->resolveSafePath($path, $isPublic);

        $dir = dirname($fullPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $targetDir = $isPublic ? $this->publicPath : $this->privatePath;
        $baseReal = realpath($targetDir);
        $dirReal = realpath($dir);
        if ($baseReal === false || $dirReal === false || ($dirReal !== $baseReal && !str_starts_with($dirReal, $baseReal . DIRECTORY_SEPARATOR))) {
            throw new InvalidArgumentException("Storage destination escapes root boundary: '{$path}'");
        }

        if (file_put_contents($fullPath, $contents) === false) {
            throw new RuntimeException("Failed to write file to storage: '{$fullPath}'");
        }

        return $fullPath;
    }

    public function get(string $path, bool $isPublic = false): ?string
    {
        $fullPath = $this->resolveSafePath($path, $isPublic);

        if (!file_exists($fullPath) || is_dir($fullPath)) {
            return null;
        }

        $data = file_get_contents($fullPath);
        return $data === false ? null : $data;
    }

    public function delete(string $path, bool $isPublic = false): bool
    {
        $fullPath = $this->resolveSafePath($path, $isPublic);

        return (file_exists($fullPath) && !is_dir($fullPath)) ? unlink($fullPath) : false;
    }

    public function getPrivatePath(): string
    {
        return $this->privatePath;
    }
}

