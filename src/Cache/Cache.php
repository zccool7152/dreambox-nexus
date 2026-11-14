<?php
declare(strict_types=1);

namespace Nexus\Cache;

use Nexus\Database\Database;

/**
 * Multi-Driver TTL Cache Store
 */
class Cache
{
    private ?Database $db;
    private string $filePath;

    public function __construct(?Database $db = null, ?string $filePath = null)
    {
        $this->db = $db;
        $this->filePath = $filePath ?? dirname(__DIR__, 2) . '/storage/cache';
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $now = time();

        if ($this->db !== null && $this->db->isAlive()) {
            if ($this->db->getDriver() === 'mongodb') {
                $row = $this->db->mongoFindOne('cache_store', ['cache_key' => $key]);
            } else {
                $row = $this->db->fetch(
                    "SELECT cache_value, expiration FROM cache_store WHERE cache_key = ?",
                    [$key]
                );
            }

            if ($row) {
                if ((int)$row['expiration'] >= $now) {
                    return unserialize((string)$row['cache_value'], ['allowed_classes' => false]);
                }
                $this->forget($key);
            }
            return $default;
        }

        $file = $this->getFilePath($key);
        if (file_exists($file)) {
            $data = json_decode((string)file_get_contents($file), true);
            if (is_array($data) && ($data['exp'] ?? 0) >= $now) {
                return unserialize((string)$data['val'], ['allowed_classes' => false]);
            }
            @unlink($file);
        }

        return $default;
    }

    public function put(string $key, mixed $value, int $ttlSeconds = 3600): bool
    {
        $exp = time() + $ttlSeconds;
        $serialized = serialize($value);

        if ($this->db !== null && $this->db->isAlive()) {
            if ($this->db->getDriver() === 'mongodb') {
                $this->db->mongoUpdate(
                    'cache_store',
                    ['cache_key' => $key],
                    ['$set' => ['cache_key' => $key, 'cache_value' => $serialized, 'expiration' => $exp]],
                    ['upsert' => true]
                );
                return true;
            }
            $sql = "INSERT INTO cache_store (cache_key, cache_value, expiration)
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE cache_value = VALUES(cache_value), expiration = VALUES(expiration)";
            $this->db->execute($sql, [$key, $serialized, $exp]);
            return true;
        }

        $file = $this->getFilePath($key);
        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0750, true);
        }

        return file_put_contents($file, json_encode(['val' => $serialized, 'exp' => $exp]), LOCK_EX) !== false;
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    public function forget(string $key): bool
    {
        if ($this->db !== null && $this->db->isAlive()) {
            if ($this->db->getDriver() === 'mongodb') {
                $this->db->mongoDelete('cache_store', ['cache_key' => $key]);
                return true;
            }
            $this->db->execute("DELETE FROM cache_store WHERE cache_key = ?", [$key]);
            return true;
        }

        $file = $this->getFilePath($key);
        return file_exists($file) ? unlink($file) : false;
    }

    public function flush(): bool
    {
        if ($this->db !== null && $this->db->isAlive()) {
            if ($this->db->getDriver() === 'mongodb') {
                $this->db->mongoDelete('cache_store', []);
                return true;
            }
            $this->db->execute("DELETE FROM cache_store");
            return true;
        }

        $files = glob($this->filePath . '/*.cache');
        if ($files) {
            foreach ($files as $f) {
                @unlink($f);
            }
        }
        return true;
    }

    public function lock(string $name, int $seconds = 60): Lock
    {
        return new Lock($this->db, $name, $seconds, $this->filePath);
    }

    private function getFilePath(string $key): string
    {
        return $this->filePath . '/' . hash('sha256', $key) . '.cache';
    }
}

