<?php
declare(strict_types=1);

namespace Nexus\Queue;

use Nexus\Database\Database;
use Nexus\Core\Logger;
use Throwable;

/**
 * Background Queue Worker Process
 *
 * Implements SKIP LOCKED concurrent polling, dead-letter failed routing,
 * signal-aware graceful termination, and memory ceiling recycling.
 */
class Worker
{
    private Database $db;
    private ?Logger $logger;
    private bool $shouldQuit = false;

    public function __construct(Database $db, ?Logger $logger = null)
    {
        $this->db = $db;
        $this->logger = $logger;
        $this->registerSignals();
    }

    private function registerSignals(): void
    {
        if (extension_loaded('pcntl')) {
            pcntl_async_signals(true);
            pcntl_signal(SIGTERM, fn() => $this->shouldQuit = true);
            pcntl_signal(SIGINT, fn() => $this->shouldQuit = true);
        }
    }

    public function run(string $queue = 'default', int $maxJobs = 0, int $memoryLimitMb = 128, int $sleepSeconds = 3): void
    {
        $jobsProcessed = 0;

        while (!$this->shouldQuit) {
            $job = $this->popNextJob($queue);

            if ($job === null) {
                sleep($sleepSeconds);
                continue;
            }

            $this->processJob($job);
            $jobsProcessed++;

            if ($maxJobs > 0 && $jobsProcessed >= $maxJobs) {
                break;
            }

            if ((memory_get_usage(true) / 1024 / 1024) >= $memoryLimitMb) {
                break;
            }
        }
    }

    private function popNextJob(string $queue): ?array
    {
        $now = time();

        if ($this->db->getDriver() === 'mongodb') {
            $job = $this->db->mongoFindOne('queue_jobs', [
                'queue' => $queue,
                'reserved_at' => null,
                'available_at' => ['$lte' => $now],
            ], ['sort' => ['created_at' => 1]]);

            if ($job !== null && isset($job['id'])) {
                $modified = $this->db->mongoUpdate(
                    'queue_jobs',
                    ['id' => $job['id'], 'reserved_at' => null],
                    ['$set' => ['reserved_at' => $now], '$inc' => ['attempts' => 1]]
                );
                if ($modified === 0) {
                    return null;
                }
                $job['attempts'] = ((int)($job['attempts'] ?? 0)) + 1;
                return $job;
            }

            return null;
        }

        // Lock next available job using SKIP LOCKED to prevent concurrency collisions
        return $this->db->transaction(function (Database $db) use ($queue, $now): ?array {
            $job = $db->fetch(
                "SELECT id, payload, attempts FROM queue_jobs
                 WHERE queue = ? AND reserved_at IS NULL AND available_at <= ?
                 ORDER BY id ASC LIMIT 1 FOR UPDATE SKIP LOCKED",
                [$queue, $now]
            );

            if ($job) {
                $db->execute(
                    "UPDATE queue_jobs SET reserved_at = ?, attempts = attempts + 1 WHERE id = ?",
                    [$now, $job['id']]
                );
            }

            return $job;
        });
    }

    private function processJob(array $job): void
    {
        $payload = json_decode($job['payload'], true);
        $jobClass = $payload['job'] ?? null;
        $jobData = $payload['data'] ?? [];
        $isMongo = $this->db->getDriver() === 'mongodb';

        try {
            if ($jobClass !== null && class_exists($jobClass)) {
                $instance = new $jobClass();
                if (method_exists($instance, 'handle')) {
                    $instance->handle($jobData);
                }
            }

            // Job succeeded: delete from queue
            if ($isMongo) {
                $this->db->mongoDelete('queue_jobs', ['id' => $job['id']]);
            } else {
                $this->db->execute("DELETE FROM queue_jobs WHERE id = ?", [$job['id']]);
            }
        } catch (Throwable $e) {
            $attempts = (int)$job['attempts'];

            if ($attempts >= 3) {
                // Route to Dead-Letter Queue (failed_jobs)
                $exceptionText = $e->getMessage() . "\n" . $e->getTraceAsString();
                if ($isMongo) {
                    $this->db->mongoInsert('failed_jobs', [
                        'job_id' => $job['id'],
                        'queue' => $job['queue'] ?? 'default',
                        'payload' => $job['payload'],
                        'exception' => $exceptionText,
                        'failed_at' => gmdate('Y-m-d H:i:s'),
                    ]);
                    $this->db->mongoDelete('queue_jobs', ['id' => $job['id']]);
                } else {
                    $this->db->execute(
                        "INSERT INTO failed_jobs (job_id, queue, payload, exception) VALUES (?, 'default', ?, ?)",
                        [$job['id'], $job['payload'], $exceptionText]
                    );
                    $this->db->execute("DELETE FROM queue_jobs WHERE id = ?", [$job['id']]);
                }

                if ($this->logger !== null) {
                    $this->logger->error("Job [{$jobClass}] failed permanently after {$attempts} attempts.", [
                        'exception' => $e->getMessage(),
                    ]);
                }
            } else {
                // Exponential Backoff: retry in (attempts * 15) seconds
                $delay = $attempts * 15;
                if ($isMongo) {
                    $this->db->mongoUpdate(
                        'queue_jobs',
                        ['id' => $job['id']],
                        ['$set' => ['reserved_at' => null, 'available_at' => time() + $delay]]
                    );
                } else {
                    $this->db->execute(
                        "UPDATE queue_jobs SET reserved_at = NULL, available_at = ? WHERE id = ?",
                        [time() + $delay, $job['id']]
                    );
                }
            }
        }
    }
}

