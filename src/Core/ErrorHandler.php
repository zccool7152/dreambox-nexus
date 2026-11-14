<?php
declare(strict_types=1);

namespace Nexus\Core;

use ErrorException;
use Throwable;

/**
 * Hardened Global Error & Exception Handler
 *
 * Catches notices, warnings, uncaught exceptions, and fatal shutdowns.
 * Automatically redacts sensitive credentials and enforces uniform JSON error responses.
 */
class ErrorHandler
{
    private static ?Logger $logger = null;
    private static bool $debug = false;

    public static function register(?Logger $logger = null, bool $debug = false): void
    {
        self::$logger = $logger;
        self::$debug = $debug;

        error_reporting(E_ALL);

        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    public static function handleError(int $level, string $message, string $file = '', int $line = 0): bool
    {
        if (!(error_reporting() & $level)) {
            return false;
        }

        // Handle deprecation notices gracefully without halting execution
        if ($level === E_DEPRECATED || $level === E_USER_DEPRECATED) {
            if (self::$logger !== null) {
                self::$logger->warning("Deprecated: {$message}", ['file' => $file, 'line' => $line]);
            }
            return true;
        }

        throw new ErrorException($message, 0, $level, $file, $line);
    }

    public static function handleException(Throwable $e): void
    {
        $traceId = Request::generateUuidV4();
        $message = self::redactSensitiveData($e->getMessage());

        if (self::$logger !== null) {
            self::$logger->error($message, [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'code' => $e->getCode(),
                'trace' => self::$debug ? self::redactSensitiveData($e->getTraceAsString()) : '[REDACTED]',
            ], $traceId);
        }

        $statusCode = ($e->getCode() >= 400 && $e->getCode() < 600) ? (int)$e->getCode() : 500;
        
        $errorResponse = self::$debug ? [
            'exception' => get_class($e),
            'message' => $message,
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ] : [];

        $userMessage = self::$debug ? $message : 'An internal server error occurred.';
        $response = Response::error($userMessage, $errorResponse, $statusCode, [], $traceId);
        $response->send();
        exit(1);
    }

    public static function handleShutdown(): void
    {
        $error = error_get_last();
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            $e = new ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']);
            self::handleException($e);
        }
    }

    public static function redactSensitiveData(string $content): string
    {
        $patterns = [
            '/([a-zA-Z][a-zA-Z0-9+.-]*:\/\/)([^:\/\s@]+):([^@\s\/]+)@/i' => '$1[REDACTED]:[REDACTED]@',
            '/(password|secret|token|key|authorization|pan|ssn|dsn)\s*[=:]\s*[\'"][^\'"]+[\'"]/i' => '$1="[REDACTED]"',
            '/(password|secret|token|key|authorization|pan|ssn|dsn)\s*[=:]\s*[^,\s&]+/i' => '$1=[REDACTED]',
            '/Bearer\s+[A-Za-z0-9\-\._~\+\/]+=*/i' => 'Bearer [REDACTED]',
        ];

        return preg_replace(array_keys($patterns), array_values($patterns), $content) ?? $content;
    }
}

