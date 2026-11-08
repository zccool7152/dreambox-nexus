<?php
declare(strict_types=1);

namespace Nexus\Core;

/**
 * Pure-PHP Structured File Logger
 *
 * Implements thread-safe file locking (flock) and automated trace_id logging.
 */
class Logger
{
    public const DEBUG = 'DEBUG';
    public const INFO = 'INFO';
    public const NOTICE = 'NOTICE';
    public const WARNING = 'WARNING';
    public const ERROR = 'ERROR';
    public const CRITICAL = 'CRITICAL';

    private string $logPath;

    public function __construct(?string $logPath = null)
    {
        $this->logPath = $logPath ?? dirname(__DIR__, 2) . '/storage/logs/nexus.log';
    }

    public function log(string $level, string $message, array $context = [], ?string $traceId = null): void
    {
        $dir = dirname($this->logPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $timestamp = gmdate('Y-m-d H:i:s');
        $trace = $traceId ?? ($context['trace_id'] ?? 'SYSTEM');
        unset($context['trace_id']);

        $contextJson = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '';
        $line = "[{$timestamp} UTC] [{$level}] [{$trace}] {$message}{$contextJson}\n";

        $fp = fopen($this->logPath, 'ab');
        if ($fp !== false) {
            if (flock($fp, LOCK_EX)) {
                fwrite($fp, $line);
                fflush($fp);
                flock($fp, LOCK_UN);
            }
            fclose($fp);
        }
    }

    public function debug(string $message, array $context = [], ?string $traceId = null): void
    {
        $this->log(self::DEBUG, $message, $context, $traceId);
    }

    public function info(string $message, array $context = [], ?string $traceId = null): void
    {
        $this->log(self::INFO, $message, $context, $traceId);
    }

    public function warning(string $message, array $context = [], ?string $traceId = null): void
    {
        $this->log(self::WARNING, $message, $context, $traceId);
    }

    public function error(string $message, array $context = [], ?string $traceId = null): void
    {
        $this->log(self::ERROR, $message, $context, $traceId);
    }

    public function critical(string $message, array $context = [], ?string $traceId = null): void
    {
        $this->log(self::CRITICAL, $message, $context, $traceId);
    }
}

