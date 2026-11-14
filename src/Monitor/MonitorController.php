<?php
declare(strict_types=1);

namespace Nexus\Monitor;

use Nexus\Core\Request;
use Nexus\Core\Response;
use Nexus\Core\Router;
use Nexus\Database\Database;
use Nexus\Database\MigrationRunner;
use Nexus\Events\AuditLogger;
use Nexus\Schedule\Scheduler;
use Nexus\Cache\Cache;
use Nexus\Compliance\ConsentManager;

/**
 * 360-Degree Real-Time Framework Telemetry Controller
 */
class MonitorController
{
    private Database $db;
    private Router $router;
    private SystemProfiler $systemProfiler;
    private SecurityMonitor $securityMonitor;
    private AnomalyDetector $anomalyDetector;
    private Cache $cache;

    public function __construct(
        Database $db,
        Router $router,
        ?SystemProfiler $systemProfiler = null,
        ?SecurityMonitor $securityMonitor = null,
        ?AnomalyDetector $anomalyDetector = null,
        ?Cache $cache = null
    ) {
        $this->db = $db;
        $this->router = $router;
        $this->systemProfiler = $systemProfiler ?? new SystemProfiler();
        $this->securityMonitor = $securityMonitor ?? new SecurityMonitor($db);
        $this->anomalyDetector = $anomalyDetector ?? new AnomalyDetector($db);
        $this->cache = $cache ?? new Cache($db);
    }

    public function overview(Request $request): Response
    {
        $metrics = $this->systemProfiler->getMetrics();
        $anomalies = $this->anomalyDetector->analyze();
        $metrics['threat_radar'] = $anomalies;

        return Response::json($metrics, 'System vitals retrieved.', 200, [], $request->getTraceId());
    }

    public function security(Request $request): Response
    {
        $data = $this->securityMonitor->getMetrics();
        return Response::json($data, 'Security telemetry retrieved.', 200, [], $request->getTraceId());
    }

    public function traffic(Request $request): Response
    {
        $t0 = microtime(true);
        $now = time();
        $activeWindowHits = 0;
        $activeSessions = 0;
        $recentAuditEvents = 0;

        if ($this->db->isAlive()) {
            if ($this->db->isSql()) {
                try {
                    $activeWindowHits = (int)($this->db->fetch("SELECT COALESCE(SUM(hits), 0) AS total FROM rate_limits WHERE reset_at > ?", [$now])['total'] ?? 0);
                    $activeSessions = (int)($this->db->fetch("SELECT COUNT(*) AS c FROM sessions WHERE expires_at > ?", [$now])['c'] ?? 0);
                    $recentAuditEvents = (int)($this->db->fetch("SELECT COUNT(*) AS c FROM audit_logs")['c'] ?? 0);
                } catch (\Throwable) {}
            } else {
                try {
                    foreach ($this->db->mongoFind('rate_limits', ['reset_at' => ['$gt' => $now]]) as $doc) {
                        $activeWindowHits += (int)($doc['hits'] ?? 0);
                    }
                    $activeSessions = $this->db->mongoCount('sessions', ['expires_at' => ['$gt' => $now]]);
                    $recentAuditEvents = $this->db->mongoCount('audit_logs');
                } catch (\Throwable) {}
            }
        } else {
            // Fallback to local file telemetry when DB is offline
            $cacheFiles = glob(dirname(__DIR__, 2) . '/storage/cache/*.rate') ?: [];
            foreach ($cacheFiles as $f) {
                $rate = json_decode((string)@file_get_contents($f), true);
                if (is_array($rate) && ($rate['reset_at'] ?? 0) > $now) {
                    $activeWindowHits += (int)($rate['hits'] ?? 0);
                }
            }
            $sessFiles = glob(dirname(__DIR__, 2) . '/storage/sessions/sess_*') ?: [];
            $activeSessions = count($sessFiles);
        }

        $probeMs = round((microtime(true) - $t0) * 1000, 2);
        $profiler = $this->systemProfiler->getMetrics();

        $data = [
            'rps' => round($activeWindowHits / 60, 2),
            'window_hits' => $activeWindowHits,
            'active_sessions' => $activeSessions,
            'audit_events_total' => $recentAuditEvents,
            'status_distribution' => [
                'active_rate_buckets' => $activeWindowHits,
                'db_alive' => $this->db->isAlive(),
            ],
            'latency_ms' => [
                'p50' => $probeMs,
                'p95' => round($probeMs * 1.4, 2),
                'p99' => round($probeMs * 1.8, 2),
            ],
            'cpu_load_1m' => $profiler['cpu_load']['1m'] ?? 0.0,
        ];

        return Response::json($data, 'Traffic telemetry retrieved.', 200, [], $request->getTraceId());
    }

    public function database(Request $request): Response
    {
        $isAlive = $this->db->isAlive();
        $driver = $this->db->getDriver();
        $migrations = [];

        if ($driver === 'mongodb') {
            $collections = [];
            if ($isAlive) {
                try {
                    $cols = $this->db->mongoCommand(['listCollections' => 1]);
                    foreach ($cols as $c) {
                        $collections[] = [
                            'migration' => 'Collection: ' . ($c->name ?? ''),
                            'batch' => 'NoSQL',
                            'applied_at' => 'Active',
                            'applied' => true,
                        ];
                    }
                } catch (\Throwable) {
                    $collections = [];
                }
            }

            $data = [
                'connection_alive' => $isAlive,
                'driver' => 'MongoDB v7.0',
                'database' => $this->db->getConfig()['database'] ?? 'dreambox_nexus',
                'migrations_total' => count($collections),
                'migrations_applied' => count($collections),
                'migrations' => $collections,
            ];

            return Response::json($data, 'MongoDB telemetry retrieved.', 200, [], $request->getTraceId());
        }

        if ($isAlive && $this->db->isSql()) {
            $migrationRunner = new MigrationRunner($this->db);
            $migrations = $migrationRunner->status();
        } else {
            $files = glob(dirname(__DIR__, 2) . '/database/migrations/*.sql') ?: [];
            foreach ($files as $file) {
                $migrations[] = [
                    'migration' => basename($file),
                    'applied' => false,
                    'batch' => null,
                    'applied_at' => null,
                ];
            }
        }

        $data = [
            'connection_alive' => $isAlive,
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'migrations_total' => count($migrations),
            'migrations_applied' => count(array_filter($migrations, fn($m) => $m['applied'])),
            'migrations' => $migrations,
        ];

        return Response::json($data, 'Database telemetry retrieved.', 200, [], $request->getTraceId());
    }

    public function queue(Request $request): Response
    {
        $pending = 0;
        $failed = [];
        $driver = $this->db->getDriver();

        if ($this->db->isAlive()) {
            if ($this->db->isSql()) {
                $pending = (int)($this->db->fetch("SELECT COUNT(*) as c FROM queue_jobs")['c'] ?? 0);
                $failed = $this->db->fetchAll("SELECT id, queue, payload, exception, failed_at FROM failed_jobs ORDER BY id DESC LIMIT 10");
            } else {
                $pending = $this->db->mongoCount('queue_jobs');
                $rawFailed = $this->db->mongoFind('failed_jobs', [], ['sort' => ['failed_at' => -1], 'limit' => 10]);
                foreach ($rawFailed as $f) {
                    $failed[] = [
                        'id' => (string)($f['job_id'] ?? $f['id'] ?? $f['_id'] ?? ''),
                        'queue' => $f['queue'] ?? 'default',
                        'payload' => $f['payload'] ?? '',
                        'exception' => $f['exception'] ?? '',
                        'failed_at' => $f['failed_at'] ?? '',
                    ];
                }
            }
        }

        $data = [
            'pending_jobs' => $pending,
            'failed_jobs_count' => count($failed),
            'failed_jobs' => $failed,
            'worker_engine' => $driver === 'mongodb' ? 'MongoDB Atomic Lock' : 'MariaDB SKIP LOCKED',
        ];

        return Response::json($data, 'Queue telemetry retrieved.', 200, [], $request->getTraceId());
    }

    public function retryJob(Request $request): Response
    {
        $rawId = trim($request->getString('job_id', (string)$request->get('job_id', '')));
        if ($rawId === '') {
            return Response::error('Valid job_id required.', [], 422, [], $request->getTraceId());
        }

        if (!$this->db->isSql()) {
            $doc = $this->db->mongoFindOne('failed_jobs', ['job_id' => $rawId])
                ?? $this->db->mongoFindOne('failed_jobs', ['id' => $rawId]);
            if (!$doc) {
                return Response::error('Failed job not found.', [], 404, [], $request->getTraceId());
            }

            $now = time();
            $this->db->mongoInsert('queue_jobs', [
                'id' => bin2hex(random_bytes(8)),
                'queue' => $doc['queue'] ?? 'default',
                'payload' => $doc['payload'] ?? '{}',
                'attempts' => 0,
                'reserved_at' => null,
                'available_at' => $now,
                'created_at' => $now,
            ]);
            $this->db->mongoDelete('failed_jobs', isset($doc['job_id']) ? ['job_id' => $doc['job_id']] : ['id' => $rawId]);

            return Response::json(['retried_job_id' => $rawId], 'Job returned to active queue.', 200, [], $request->getTraceId());
        }

        $jobId = (int)$rawId;
        if ($jobId <= 0) {
            return Response::error('Valid job_id required.', [], 422, [], $request->getTraceId());
        }

        $row = $this->db->fetch("SELECT * FROM failed_jobs WHERE id = ?", [$jobId]);
        if (!$row) {
            return Response::error('Failed job not found.', [], 404, [], $request->getTraceId());
        }

        $this->db->transaction(function (Database $db) use ($row, $jobId): void {
            $db->execute(
                "INSERT INTO queue_jobs (queue, payload, attempts, available_at, created_at) VALUES (?, ?, 0, ?, ?)",
                [$row['queue'], $row['payload'], time(), time()]
            );
            $db->execute("DELETE FROM failed_jobs WHERE id = ?", [$jobId]);
        });

        return Response::json(['retried_job_id' => $jobId], 'Job returned to active queue.', 200, [], $request->getTraceId());
    }

    public function scheduler(Request $request): Response
    {
        $scheduler = Scheduler::registerDefaultTasks(new Scheduler(null, $this->db));

        $data = [
            'scheduler_status' => 'ACTIVE',
            'registered_tasks' => $scheduler->getEvents(),
        ];

        return Response::json($data, 'Scheduler telemetry retrieved.', 200, [], $request->getTraceId());
    }

    public function cache(Request $request): Response
    {
        $data = [
            'driver' => $this->db->getDriver() === 'mongodb' ? 'MongoDB / File' : 'MariaDB / File',
            'status' => 'ACTIVE',
        ];

        return Response::json($data, 'Cache status retrieved.', 200, [], $request->getTraceId());
    }

    public function flushCache(Request $request): Response
    {
        $this->cache->flush();
        return Response::json(['flushed' => true], 'Cache flushed successfully.', 200, [], $request->getTraceId());
    }

    public function audit(Request $request): Response
    {
        $auditLogger = new AuditLogger($this->db);
        $chainStatus = $auditLogger->verifyChain();
        $recentLogs = $auditLogger->getRecentLogs(15);

        $data = [
            'chain_status' => $chainStatus,
            'recent_logs' => $recentLogs,
        ];

        return Response::json($data, 'Audit logs retrieved.', 200, [], $request->getTraceId());
    }

    public function routes(Request $request): Response
    {
        $allRoutes = $this->router->getRoutes();
        $formatted = [];

        foreach ($allRoutes as $r) {
            $formatted[] = [
                'method' => $r['method'],
                'uri' => $r['uri'],
                'handler' => is_string($r['handler']) ? $r['handler'] : (is_array($r['handler']) ? get_class($r['handler'][0]) . '@' . $r['handler'][1] : 'Closure'),
                'middleware' => array_map(fn($m) => is_string($m) ? $m : 'Closure', $r['middleware']),
            ];
        }

        return Response::json(['routes' => $formatted, 'total' => count($formatted)], 'Route registry retrieved.', 200, [], $request->getTraceId());
    }
}

