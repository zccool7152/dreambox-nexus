<?php
declare(strict_types=1);

namespace Nexus\Security;

use Nexus\Database\Database;

/**
 * Hardened Session Manager
 *
 * Enforces HIPAA 15-minute idle timeouts, SameSite=Strict cookies,
 * and automated session fixation regeneration.
 */
class Session
{
    private ?Database $db;
    private int $idleTimeoutSeconds;
    private bool $secureCookie;

    public function __construct(?Database $db = null, int $idleTimeoutMinutes = 15, bool $secureCookie = false)
    {
        $this->db = $db;
        $this->idleTimeoutSeconds = $idleTimeoutMinutes * 60;
        $this->secureCookie = $secureCookie;
    }

    public function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $sessionPath = dirname(__DIR__, 2) . '/storage/sessions';
        if (!is_dir($sessionPath)) {
            mkdir($sessionPath, 0700, true);
        }

        if (!headers_sent()) {
            @ini_set('session.save_path', $sessionPath);
            @ini_set('session.use_strict_mode', '1');
            @ini_set('session.use_only_cookies', '1');

            @session_set_cookie_params([
                'lifetime' => 0, // Session cookie (dies on browser close)
                'path' => '/',
                'domain' => '',
                'secure' => $this->secureCookie,
                'httponly' => true,
                'samesite' => 'Strict',
            ]);

            @session_name('nexus_session');
        }

        @session_start();

        // Enforce HIPAA Inactivity Timeout
        $now = time();
        $lastActivity = $_SESSION['__last_activity'] ?? $now;

        if (($now - $lastActivity) > $this->idleTimeoutSeconds) {
            $this->destroy();
            session_start();
            $_SESSION['__timeout_expired'] = true;
        }

        $_SESSION['__last_activity'] = $now;
    }

    public function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
            @session_regenerate_id(true);
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

    public function destroy(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params["path"],
                    $params["domain"],
                    $params["secure"],
                    $params["httponly"]
                );
            }
            session_destroy();
        }
    }

    public function getId(): string
    {
        return session_id() ?: '';
    }
}

