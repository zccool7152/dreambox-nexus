<?php
declare(strict_types=1);

namespace Nexus\Queue;

use Nexus\Database\Database;
use RuntimeException;

/**
 * MariaDB Concurrency-Safe Queue Engine
 */
class Queue
{
    private Database $db;
    private string $defaultQueue;

    public function __construct(Database $db, string $defaultQueue = 'default')
    {
        $this->db = $db;
        $this->defaultQueue = $defaultQueue;
    }

    public function push(string $jobClass, array $data = [], int $delaySeconds = 0, ?string $queue = null): string
    {
        $targetQueue = $queue ?? $this->defaultQueue;
        $now = time();
        $availableAt = $now + $delaySeconds;

        $payload = json_encode([
            'job' => $jobClass,
            'data' => $data,
            'pushed_at' => $now,
        ], JSON_THROW_ON_ERROR);

        $sql = "INSERT INTO queue_jobs (queue, payload, attempts, available_at, created_at)
                VALUES (?, ?, 0, ?, ?)";

        $this->db->execute($sql, [$targetQueue, $payload, $availableAt, $now]);
        return $this->db->lastInsertId();
    }
}

