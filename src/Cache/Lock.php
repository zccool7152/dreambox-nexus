<?php
declare(strict_types=1);

namespace Nexus\Cache;

use Nexus\Database\Database;

/**
 * Atomic Distributed Mutex Lock
 */
class Lock
{
    private ?Database $db;
    private string $name;
    private int $seconds;
    private string $filePath;
    private $fp = null;

    public function __construct(?Database $db, string $name, int $seconds, string $filePath)
    {
        $this->db = $db;
        $this->name = $name;
        $this->seconds = $seconds;
        $this->filePath = $filePath;
    }

    public function acquire(): bool
    {
        $lockKey = 'lock:' . $this->name;
        $now = time();
        $expiresAt = $now + $this->seconds;

        if ($this->db !== null && $this->db->isAlive()) {
            if ($this->db->getDriver() === 'mongodb') {
                $row = $this->db->mongoFindOne('cache_store', ['cache_key' => $lockKey]);
                if ($row && (int)($row['expiration'] ?? 0) > $now) {
                    return false; // Already locked
                }

                $this->db->mongoUpdate(
                    'cache_store',
                    ['cache_key' => $lockKey],
                    ['$set' => [
                        'cache_key' => $lockKey,
                        'cache_value' => 'locked',
                        'expiration' => $expiresAt,
                    ]],
                    ['upsert' => true]
                );
                return true;
            }

            $row = $this->db->fetch("SELECT expiration FROM cache_store WHERE cache_key = ?", [$lockKey]);
            if ($row && (int)$row['expiration'] > $now) {
                return false; // Already locked
            }

            $sql = "INSERT INTO cache_store (cache_key, cache_value, expiration)
                    VALUES (?, 'locked', ?)
                    ON DUPLICATE KEY UPDATE
                        expiration = IF(expiration < {$now}, {$expiresAt}, expiration)";
            $this->db->execute($sql, [$lockKey, $expiresAt]);

            $check = $this->db->fetch("SELECT expiration FROM cache_store WHERE cache_key = ?", [$lockKey]);
            return (int)($check['expiration'] ?? 0) === $expiresAt;
        }

        if (!is_dir($this->filePath)) {
            @mkdir($this->filePath, 0755, true);
        }
        $file = $this->filePath . '/' . hash('sha256', $lockKey) . '.lock';
        $this->fp = fopen($file, 'c+');
        if ($this->fp !== false) {
            return flock($this->fp, LOCK_EX | LOCK_NB);
        }

        return false;
    }

    public function release(): void
    {
        $lockKey = 'lock:' . $this->name;

        if ($this->db !== null && $this->db->isAlive()) {
            if ($this->db->getDriver() === 'mongodb') {
                $this->db->mongoDelete('cache_store', ['cache_key' => $lockKey]);
                return;
            }

            $this->db->execute("DELETE FROM cache_store WHERE cache_key = ?", [$lockKey]);
            return;
        }

        if ($this->fp !== null) {
            flock($this->fp, LOCK_UN);
            fclose($this->fp);
            $this->fp = null;
        }
    }
}

