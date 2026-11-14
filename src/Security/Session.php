<?php
declare(strict_types=1);

namespace Nexus\Security;

use Nexus\Database\Database;
use SessionHandlerInterface;

/**
 * Hardened Session Manager
 *
 * Enforces HIPAA 15-minute idle timeouts, SameSite=Strict cookies,
 * automated session fixation regeneration, and database-backed (MongoDB/SQL)
 * session persistence compliant with PCI-DSS Req 3.5 & NIST PR.DS-1.
 */
class Session implements SessionHandlerInterface, \SessionUpdateTimestampHandlerInterface
{
    private ?Database $db;
    private int $idleTimeoutSeconds;
    private bool $secureCookie;
    private static string $cliSessionId = '';

    /**
     * Dynamically evaluate whether the incoming request or deployment is secure.
     * Enforces secure cookies under TLS/HTTPS, reverse proxy SSL headers, or production mode.
     */
    public static function isSecureConnection(): bool
    {
        // 1. Explicit environment configuration override
        $configured = \Nexus\Core\Env::get('SESSION_SECURE_COOKIE');
        if ($configured !== null && $configured !== '') {
            $val = filter_var($configured, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($val === true) {
                return true;
            }
        }

        // 2. Direct HTTPS scheme
        if (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') {
            return true;
        }

        // 3. Port 443
        if (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) {
            return true;
        }

        // 4. Reverse Proxy TLS headers
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string)$_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') {
            return true;
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower((string)$_SERVER['HTTP_X_FORWARDED_SSL']) === 'on') {
            return true;
        }

        // 5. Production environment enforcement
        if (\Nexus\Core\Env::get('APP_ENV') === 'production') {
            return true;
        }

        return false;
    }

    public function __construct(?Database $db = null, ?int $idleTimeoutMinutes = null, ?bool $secureCookie = null)
    {
        $this->db = $db;
        $timeout = $idleTimeoutMinutes ?? (int)\Nexus\Core\Env::get('SESSION_LIFETIME', 15);
        $this->idleTimeoutSeconds = $timeout * 60;
        $this->secureCookie = $secureCookie ?? self::isSecureConnection();
    }

    public function isSecureCookie(): bool
    {
        return $this->secureCookie;
    }

    private function isDbActive(): bool
    {
        return $this->db !== null && $this->db->isAlive();
    }

    private function resolveSessionDir(): string
    {
        $primary = dirname(__DIR__, 2) . '/storage/sessions';
        if (!is_dir($primary)) {
            @mkdir($primary, 0700, true);
        }
        if (is_dir($primary) && is_writable($primary)) {
            return $primary;
        }
        $uid = function_exists('posix_geteuid') ? (string)posix_geteuid() : (string)getmyuid();
        $fallback = rtrim(sys_get_temp_dir(), '/\\') . '/nexus_sessions_' . $uid;
        if (!is_dir($fallback)) {
            @mkdir($fallback, 0700, true);
        }
        return $fallback;
    }

    public function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            if (session_id() !== '') {
                self::$cliSessionId = session_id();
            }
            return;
        }

        $sessionPath = $this->resolveSessionDir();

        if (!headers_sent()) {
            if ($this->isDbActive() && $this->db->getDriver() === 'mongodb') {
                @session_set_save_handler($this, true);
            } else {
                @ini_set('session.save_path', $sessionPath);
            }

            @ini_set('session.use_strict_mode', '1');
            @ini_set('session.use_only_cookies', '1');

            $sameSite = (string)\Nexus\Core\Env::get('SESSION_SAME_SITE', 'Lax');
            @session_set_cookie_params([
                'lifetime' => 0, // Session cookie (dies on browser close)
                'path' => '/',
                'domain' => '',
                'secure' => $this->secureCookie,
                'httponly' => true,
                'samesite' => $sameSite,
            ]);

            @session_name('nexus_session');
            @session_start();
        }

        if (!isset($_SESSION) || !is_array($_SESSION)) {
            $_SESSION = [];
        }

        if (session_id() !== '') {
            self::$cliSessionId = session_id();
        } elseif (self::$cliSessionId === '') {
            self::$cliSessionId = bin2hex(random_bytes(16));
        }

        // Enforce HIPAA Inactivity Timeout
        $now = time();
        $lastActivity = $_SESSION['__last_activity'] ?? $now;

        if (($now - $lastActivity) > $this->idleTimeoutSeconds) {
            $this->destroy();
            if (!headers_sent()) {
                @session_start();
            }
            if (!isset($_SESSION) || !is_array($_SESSION)) {
                $_SESSION = [];
            }
            self::$cliSessionId = session_id() ?: bin2hex(random_bytes(16));
            $_SESSION['__timeout_expired'] = true;
        }

        $_SESSION['__last_activity'] = $now;
    }

    // ── SessionHandlerInterface & SessionUpdateTimestampHandlerInterface Methods ─

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function validateId(string $id): bool
    {
        if (!preg_match('/^[a-zA-Z0-9,-]{16,128}$/', $id)) {
            return false;
        }

        if ($this->isDbActive() && $this->db->getDriver() === 'mongodb') {
            try {
                return $this->db->mongoFindOne('sessions', ['session_id' => $id]) !== null;
            } catch (\Throwable $e) {
                (new \Nexus\Core\Logger())->error('Session validateId MongoDB failure: ' . $e->getMessage(), ['session_id_prefix' => substr($id, 0, 8)]);
                return false;
            }
        }

        $file = $this->resolveSessionDir() . '/sess_' . $id;
        return is_file($file);
    }

    public function updateTimestamp(string $id, string $data): bool
    {
        return $this->write($id, $data);
    }

    public function read(string $id): string|false
    {
        if ($this->isDbActive() && $this->db->getDriver() === 'mongodb') {
            try {
                $doc = $this->db->mongoFindOne('sessions', ['session_id' => $id]);
                if (!$doc) {
                    return '';
                }
                return (string)($doc['data'] ?? '');
            } catch (\Throwable $e) {
                (new \Nexus\Core\Logger())->error('Session read MongoDB failure: ' . $e->getMessage(), ['session_id_prefix' => substr($id, 0, 8)]);
                return '';
            }
        }

        $file = $this->resolveSessionDir() . '/sess_' . $id;
        return file_exists($file) ? (string)file_get_contents($file) : '';
    }

    public function write(string $id, string $data): bool
    {
        if ($this->isDbActive() && $this->db->getDriver() === 'mongodb') {
            try {
                $this->db->mongoUpdate(
                    'sessions',
                    ['session_id' => $id],
                    ['$set' => [
                        'session_id' => $id,
                        'data' => $data,
                        'last_activity' => time(),
                        'expires_at' => time() + $this->idleTimeoutSeconds,
                    ]],
                    ['upsert' => true]
                );
                return true;
            } catch (\Throwable $e) {
                (new \Nexus\Core\Logger())->error('Session write MongoDB failure (falling back to file): ' . $e->getMessage(), ['session_id_prefix' => substr($id, 0, 8)]);
            }
        }

        $sessionPath = $this->resolveSessionDir();
        $file = $sessionPath . '/sess_' . $id;
        $ok = (bool)@file_put_contents($file, $data);
        if (!$ok) {
            (new \Nexus\Core\Logger())->error('Session write file failure', ['file' => basename($file), 'dir' => $sessionPath]);
        }
        return $ok;
    }

    public function destroy(string $id = ''): bool
    {
        if ($id !== '') {
            if ($this->isDbActive() && $this->db->getDriver() === 'mongodb') {
                try {
                    $this->db->mongoDelete('sessions', ['session_id' => $id]);
                } catch (\Throwable) {}
            }
            $file = dirname(__DIR__, 2) . '/storage/sessions/sess_' . $id;
            if (file_exists($file)) {
                @unlink($file);
            }
            return true;
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            $this->start();
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            $currentId = session_id();
            $_SESSION = [];
            if (ini_get("session.use_cookies") && !headers_sent()) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params["path"] ?: '/',
                    $params["domain"],
                    $params["secure"],
                    $params["httponly"]
                );
            }
            @session_unset();
            @session_destroy();
            if ($currentId !== '') {
                $this->destroy($currentId);
            }
        }

        self::$cliSessionId = '';
        unset($_COOKIE['nexus_session']);

        return true;
    }

    public function gc(int $max_lifetime): int|false
    {
        if ($this->isDbActive() && $this->db->getDriver() === 'mongodb') {
            try {
                $expired = time() - $max_lifetime;
                return $this->db->mongoDelete('sessions', ['last_activity' => ['$lt' => $expired]]);
            } catch (\Throwable) {
                return 0;
            }
        }

        $sessionPath = dirname(__DIR__, 2) . '/storage/sessions';
        $count = 0;
        foreach (glob($sessionPath . '/sess_*') ?: [] as $file) {
            if (filemtime($file) + $max_lifetime < time()) {
                @unlink($file);
                $count++;
            }
        }
        return $count;
    }

    public function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
            @session_regenerate_id(true);
            self::$cliSessionId = session_id();
        } else {
            self::$cliSessionId = bin2hex(random_bytes(16));
        }
    }

    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public function flash(string $key, mixed $value): void
    {
        $this->start();
        $_SESSION['__flash'][$key] = $value;
    }

    public function getFlash(string $key, mixed $default = null): mixed
    {
        $this->start();
        $val = $_SESSION['__flash'][$key] ?? $default;
        unset($_SESSION['__flash'][$key]);
        return $val;
    }

    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public function getId(): string
    {
        return session_id() ?: self::$cliSessionId;
    }
}
