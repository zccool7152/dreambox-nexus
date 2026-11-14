<?php
declare(strict_types=1);

namespace Nexus\Schedule;

use Closure;
use InvalidArgumentException;
use Nexus\Cache\Lock;
use Nexus\Core\Logger;
use Nexus\Database\Database;
use Throwable;

/**
 * Single-Cron Centralized Task Scheduler
 */
class Scheduler
{
    private array $events = [];
    private ?Logger $logger;
    private ?Database $db;
    private string $lockPath;

    public function __construct(?Logger $logger = null, ?Database $db = null, ?string $lockPath = null)
    {
        $this->logger = $logger;
        $this->db = $db;
        $this->lockPath = $lockPath ?? dirname(__DIR__, 2) . '/storage/cache';
    }

    public static function registerDefaultTasks(self $scheduler): self
    {
        $scheduler->command('php bin/nexus cache:clear', 'Prune Temporary Files')->daily();
        $scheduler->command('php bin/nexus compliance:verify', 'Compliance Audit')->weekly();
        return $scheduler;
    }

    public function call(Closure|callable $callback, string $name = 'anonymous'): static
    {
        $this->events[] = [
            'type' => 'callback',
            'handler' => $callback,
            'name' => $name,
            'interval' => '* * * * *',
            'last_run' => null,
        ];
        return $this;
    }

    public function command(string $command, string $name = ''): static
    {
        $this->assertSafeCommand($command);

        $this->events[] = [
            'type' => 'command',
            'handler' => $command,
            'name' => $name ?: $command,
            'interval' => '* * * * *',
            'last_run' => null,
        ];
        return $this;
    }

    private function assertSafeCommand(string $command): void
    {
        if (trim($command) === '' || preg_match('/[;&|`$><\r\n]/', $command)) {
            throw new InvalidArgumentException("Unsafe shell metacharacters detected in scheduled command.");
        }
    }

    public function everyMinute(): static
    {
        $this->setLastInterval('* * * * *');
        return $this;
    }

    public function hourly(): static
    {
        $this->setLastInterval('0 * * * *');
        return $this;
    }

    public function daily(): static
    {
        $this->setLastInterval('0 0 * * *');
        return $this;
    }

    public function weekly(): static
    {
        $this->setLastInterval('0 0 * * 0');
        return $this;
    }

    public function monthly(): static
    {
        $this->setLastInterval('0 0 1 * *');
        return $this;
    }

    private function setLastInterval(string $cron): void
    {
        $idx = count($this->events) - 1;
        if ($idx >= 0) {
            $this->events[$idx]['interval'] = $cron;
        }
    }

    public function getEvents(): array
    {
        $state = $this->loadState();
        $events = [];
        foreach ($this->events as $event) {
            $name = $event['name'];
            if (isset($state[$name])) {
                $event['last_run'] = $state[$name]['last_run'] ?? $event['last_run'];
                $event['last_success'] = $state[$name]['success'] ?? null;
                $event['last_duration_ms'] = $state[$name]['duration_ms'] ?? null;
            }
            $events[] = $event;
        }
        return $events;
    }

    public function run(): array
    {
        $executed = [];
        $state = $this->loadState();

        foreach ($this->events as &$event) {
            $lock = new Lock($this->db, 'schedule:' . md5($event['name']), 300, $this->lockPath);
            if (!$lock->acquire()) {
                $executed[] = [
                    'name' => $event['name'],
                    'success' => false,
                    'skipped' => true,
                    'duration_ms' => 0.0,
                    'output' => 'Skipped: overlapping execution prevented by mutex lock.',
                ];
                continue;
            }

            $startTime = microtime(true);
            $success = true;
            $output = '';

            try {
                if ($event['type'] === 'callback') {
                    $handler = $event['handler'];
                    $res = $handler();
                    $output = is_string($res) ? $res : 'Done';
                } else {
                    $this->assertSafeCommand((string)$event['handler']);
                    $safeCmd = escapeshellcmd((string)$event['handler']);
                    $output = (string)shell_exec($safeCmd);
                }
            } catch (Throwable $e) {
                $success = false;
                $output = $e->getMessage();
                if ($this->logger !== null) {
                    $this->logger->error("Scheduled task [{$event['name']}] failed: " . $e->getMessage());
                }
            } finally {
                $lock->release();
            }

            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $now = time();
            $event['last_run'] = $now;

            $state[$event['name']] = [
                'last_run' => $now,
                'success' => $success,
                'duration_ms' => $duration,
            ];

            $executed[] = [
                'name' => $event['name'],
                'success' => $success,
                'duration_ms' => $duration,
                'output' => trim($output),
            ];
        }

        $this->saveState($state);

        return $executed;
    }

    private function loadState(): array
    {
        $stateFile = $this->lockPath . '/scheduler_state.json';
        if (!file_exists($stateFile)) {
            return [];
        }
        $raw = (string)@file_get_contents($stateFile);
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function saveState(array $state): void
    {
        if (!is_dir($this->lockPath)) {
            @mkdir($this->lockPath, 0755, true);
        }
        $stateFile = $this->lockPath . '/scheduler_state.json';
        @file_put_contents($stateFile, json_encode($state, JSON_PRETTY_PRINT), LOCK_EX);
    }
}

