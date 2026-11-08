<?php
declare(strict_types=1);

namespace Nexus\Schedule;

use Closure;
use Nexus\Core\Logger;
use Throwable;

/**
 * Single-Cron Centralized Task Scheduler
 */
class Scheduler
{
    private array $events = [];
    private ?Logger $logger;

    public function __construct(?Logger $logger = null)
    {
        $this->logger = $logger;
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
        $this->events[] = [
            'type' => 'command',
            'handler' => $command,
            'name' => $name ?: $command,
            'interval' => '* * * * *',
            'last_run' => null,
        ];
        return $this;
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
        return $this->events;
    }

    public function run(): array
    {
        $executed = [];

        foreach ($this->events as &$event) {
            $startTime = microtime(true);
            $success = true;
            $output = '';

            try {
                if ($event['type'] === 'callback') {
                    $handler = $event['handler'];
                    $res = $handler();
                    $output = is_string($res) ? $res : 'Done';
                } else {
                    $output = (string)shell_exec($event['handler']);
                }
            } catch (Throwable $e) {
                $success = false;
                $output = $e->getMessage();
                if ($this->logger !== null) {
                    $this->logger->error("Scheduled task [{$event['name']}] failed: " . $e->getMessage());
                }
            }

            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $event['last_run'] = time();

            $executed[] = [
                'name' => $event['name'],
                'success' => $success,
                'duration_ms' => $duration,
                'output' => trim($output),
            ];
        }

        return $executed;
    }
}

