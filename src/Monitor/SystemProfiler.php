<?php
declare(strict_types=1);

namespace Nexus\Monitor;

/**
 * System Runtime & Performance Telemetry Profiler
 */
class SystemProfiler
{
    private string $storagePath;

    public function __construct(?string $storagePath = null)
    {
        $this->storagePath = $storagePath ?? dirname(__DIR__, 2) . '/storage';
    }

    public function getMetrics(): array
    {
        // 1. Memory Consumption
        $memoryUsage = memory_get_usage(true);
        $peakMemory = memory_get_peak_usage(true);
        $memoryLimit = ini_get('memory_limit');

        // 2. CPU Load (WSL2 / Linux)
        $cpuLoad = function_exists('sys_getloadavg') ? (sys_getloadavg() ?: [0.0, 0.0, 0.0]) : [0.0, 0.0, 0.0];

        // 3. Zend OPcache Statistics
        $opcacheStats = ['enabled' => false];
        if (function_exists('opcache_get_status')) {
            $status = @opcache_get_status(false);
            if (is_array($status)) {
                $mem = $status['memory_usage'] ?? [];
                $stats = $status['opcache_statistics'] ?? [];
                $opcacheStats = [
                    'enabled' => (bool)($status['opcache_enabled'] ?? false),
                    'hit_rate' => round((float)($stats['opcache_hit_rate'] ?? 0), 2),
                    'hits' => (int)($stats['hits'] ?? 0),
                    'misses' => (int)($stats['misses'] ?? 0),
                    'used_memory_mb' => round(((int)($mem['used_memory'] ?? 0)) / 1024 / 1024, 2),
                    'free_memory_mb' => round(((int)($mem['free_memory'] ?? 0)) / 1024 / 1024, 2),
                    'cached_scripts' => (int)($stats['num_cached_scripts'] ?? 0),
                ];
            }
        }

        // 4. Storage Breakdown
        $storageSizes = [
            'logs' => $this->getDirectorySize($this->storagePath . '/logs'),
            'cache' => $this->getDirectorySize($this->storagePath . '/cache'),
            'sessions' => $this->getDirectorySize($this->storagePath . '/sessions'),
            'private_app' => $this->getDirectorySize($this->storagePath . '/app/private'),
        ];

        return [
            'php_version' => PHP_VERSION,
            'server_os' => PHP_OS_FAMILY,
            'memory' => [
                'current_mb' => round($memoryUsage / 1024 / 1024, 2),
                'peak_mb' => round($peakMemory / 1024 / 1024, 2),
                'limit' => $memoryLimit,
            ],
            'cpu_load' => [
                '1m' => round($cpuLoad[0], 2),
                '5m' => round($cpuLoad[1], 2),
                '15m' => round($cpuLoad[2], 2),
            ],
            'opcache' => $opcacheStats,
            'storage_bytes' => $storageSizes,
            'storage_formatted' => array_map([$this, 'formatBytes'], $storageSizes),
        ];
    }

    private function getDirectorySize(string $dir): int
    {
        if (!is_dir($dir)) {
            return 0;
        }

        $size = 0;
        $files = glob($dir . '/*');
        if ($files) {
            foreach ($files as $file) {
                if (is_file($file)) {
                    $size += filesize($file);
                }
            }
        }
        return $size;
    }

    public function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = (int)floor(log($bytes, 1024));
        return round($bytes / pow(1024, $i), 1) . ' ' . ($units[$i] ?? 'B');
    }
}

