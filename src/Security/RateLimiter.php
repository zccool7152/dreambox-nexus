<?php
declare(strict_types=1);

namespace Nexus\Security;

use Nexus\Core\MiddlewareInterface;
use Nexus\Core\Request;
use Nexus\Core\Response;
use Nexus\Database\Database;

/**
 * Concurrency-Safe Sliding Window Rate Limiter
 */
class RateLimiter implements MiddlewareInterface
{
    private ?Database $db;
    private int $maxAttempts;
    private int $decaySeconds;
    private string $storagePath;

    public function __construct(
        ?Database $db = null,
        int $maxAttempts = 60,
        int $decaySeconds = 60,
        ?string $storagePath = null
    ) {
        $this->db = $db;
        $this->maxAttempts = $maxAttempts;
        $this->decaySeconds = $decaySeconds;
        $this->storagePath = $storagePath ?? dirname(__DIR__, 2) . '/storage/cache';
    }

    public function handle(Request $request, callable $next): Response
    {
        $key = 'rate:' . hash('sha256', $request->getClientIp() . '|' . $request->getPath());
        $now = time();

        $rateData = $this->incrementAndGetHits($key, $now);
        $remaining = max(0, $this->maxAttempts - $rateData['hits']);
        $resetTime = $rateData['reset_at'];

        if ($rateData['hits'] > $this->maxAttempts) {
            $retryAfter = max(1, $resetTime - $now);
            return Response::error(
                'Too Many Requests. Rate limit exceeded.',
                ['rate_limit' => ["Please wait {$retryAfter} seconds before retrying."]],
                429,
                [
                    'Retry-After' => (string)$retryAfter,
                    'X-RateLimit-Limit' => (string)$this->maxAttempts,
                    'X-RateLimit-Remaining' => '0',
                    'X-RateLimit-Reset' => (string)$resetTime,
                ],
                $request->getTraceId()
            );
        }

        /** @var Response $response */
        $response = $next($request);

        $response->setHeader('X-RateLimit-Limit', (string)$this->maxAttempts);
        $response->setHeader('X-RateLimit-Remaining', (string)$remaining);
        $response->setHeader('X-RateLimit-Reset', (string)$resetTime);

        return $response;
    }

    private function incrementAndGetHits(string $key, int $now): array
    {
        if ($this->db !== null && $this->db->isAlive()) {
            return $this->dbIncrement($key, $now);
        }

        return $this->fileIncrement($key, $now);
    }

    private function dbIncrement(string $key, int $now): array
    {
        $resetAt = $now + $this->decaySeconds;

        $sql = "INSERT INTO rate_limits (key_id, hits, reset_at)
                VALUES (?, 1, ?)
                ON DUPLICATE KEY UPDATE
                    hits = IF(reset_at < {$now}, 1, hits + 1),
                    reset_at = IF(reset_at < {$now}, {$resetAt}, reset_at)";

        try {
            $this->db->execute($sql, [$key, $resetAt]);
            $row = $this->db->fetch("SELECT hits, reset_at FROM rate_limits WHERE key_id = ?", [$key]);

            if ($row) {
                return [
                    'hits' => (int)$row['hits'],
                    'reset_at' => (int)$row['reset_at'],
                ];
            }
        } catch (\Throwable) {
            // Fallback to file on DB error
        }

        return $this->fileIncrement($key, $now);
    }

    private function fileIncrement(string $key, int $now): array
    {
        if (!is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0755, true);
        }

        $filePath = $this->storagePath . '/' . $key . '.rate';
        $isNew = !file_exists($filePath);
        $fp = @fopen($filePath, 'c+');

        $hits = 1;
        $resetAt = $now + $this->decaySeconds;

        if ($fp === false) {
            return ['hits' => 1, 'reset_at' => $resetAt];
        }

        if ($isNew) {
            @chmod($filePath, 0666);
        }
            if (flock($fp, LOCK_EX)) {
                $content = stream_get_contents($fp);
                if (!empty($content)) {
                    $data = json_decode($content, true);
                    if (is_array($data) && ($data['reset_at'] ?? 0) >= $now) {
                        $hits = ((int)($data['hits'] ?? 0)) + 1;
                        $resetAt = (int)$data['reset_at'];
                    }
                }

                ftruncate($fp, 0);
                rewind($fp);
                fwrite($fp, json_encode(['hits' => $hits, 'reset_at' => $resetAt]));
                fflush($fp);
                flock($fp, LOCK_UN);
            }
        fclose($fp);

        return ['hits' => $hits, 'reset_at' => $resetAt];
    }
}

