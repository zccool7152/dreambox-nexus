<?php
declare(strict_types=1);

namespace Nexus\Auth;

use Nexus\Database\Database;

/**
 * PCI-DSS Req 8.3.4 Account Lockout Engine
 *
 * Enforces account locking after 5 consecutive failed login attempts
 * for a mandatory 30-minute cooling window.
 */
class AccountLockout
{
    private ?Database $db;
    private int $maxAttempts;
    private int $lockoutMinutes;

    public function __construct(?Database $db = null, int $maxAttempts = 5, int $lockoutMinutes = 30)
    {
        $this->db = $db;
        $this->maxAttempts = $maxAttempts;
        $this->lockoutMinutes = $lockoutMinutes;
    }

    public function isLocked(string $identity): bool
    {
        if ($this->db === null || !$this->db->isAlive()) {
            return false;
        }

        try {
            if (!$this->db->isSql()) {
                $row = $this->db->mongoFindOne('login_attempts', ['identity' => $identity]);
                if (!$row || empty($row['locked_until'])) {
                    return false;
                }
                $lockedUntil = strtotime((string)$row['locked_until']);
                return $lockedUntil > time();
            }

            $row = $this->db->fetch(
                "SELECT attempts, locked_until FROM login_attempts WHERE identity = ?",
                [$identity]
            );

            if (!$row || empty($row['locked_until'])) {
                return false;
            }

            $lockedUntil = strtotime((string)$row['locked_until']);
            return $lockedUntil > time();
        } catch (\Throwable) {
            return false;
        }
    }

    public function recordFailedAttempt(string $identity, string $ip): void
    {
        if ($this->db === null || !$this->db->isAlive()) {
            return;
        }

        try {
            if (!$this->db->isSql()) {
                $row = $this->db->mongoFindOne('login_attempts', ['identity' => $identity]);
                if (!$row) {
                    $this->db->mongoInsert('login_attempts', [
                        'identity' => $identity,
                        'ip_address' => $ip,
                        'attempts' => 1,
                        'locked_until' => null,
                        'last_attempt_at' => date('Y-m-d H:i:s'),
                    ]);
                    return;
                }

                $newAttempts = (int)($row['attempts'] ?? 0) + 1;
                $lockedUntil = null;
                if ($newAttempts >= $this->maxAttempts) {
                    $lockedUntil = date('Y-m-d H:i:s', time() + ($this->lockoutMinutes * 60));
                }

                $this->db->mongoUpdate('login_attempts', ['identity' => $identity], [
                    '$set' => [
                        'attempts' => $newAttempts,
                        'locked_until' => $lockedUntil,
                        'ip_address' => $ip,
                        'last_attempt_at' => date('Y-m-d H:i:s'),
                    ]
                ]);
                return;
            }

            $row = $this->db->fetch("SELECT id, attempts FROM login_attempts WHERE identity = ?", [$identity]);

            if (!$row) {
                $this->db->execute(
                    "INSERT INTO login_attempts (identity, ip_address, attempts, last_attempt_at) VALUES (?, ?, 1, CURRENT_TIMESTAMP)",
                    [$identity, $ip]
                );
                return;
            }

            $newAttempts = (int)$row['attempts'] + 1;
            $lockedUntil = null;

            if ($newAttempts >= $this->maxAttempts) {
                $lockedUntil = date('Y-m-d H:i:s', time() + ($this->lockoutMinutes * 60));
            }

            $this->db->execute(
                "UPDATE login_attempts SET attempts = ?, locked_until = ?, ip_address = ?, last_attempt_at = CURRENT_TIMESTAMP WHERE id = ?",
                [$newAttempts, $lockedUntil, $ip, $row['id']]
            );
        } catch (\Throwable) {
            // Fail open to avoid blocking valid authentication on telemetry errors
        }
    }

    public function resetAttempts(string $identity): void
    {
        if ($this->db === null || !$this->db->isAlive()) {
            return;
        }

        try {
            if (!$this->db->isSql()) {
                $this->db->mongoDelete('login_attempts', ['identity' => $identity]);
                return;
            }

            $this->db->execute("DELETE FROM login_attempts WHERE identity = ?", [$identity]);
        } catch (\Throwable) {
            // Safe ignore
        }
    }

    public function getLockoutRemainingSeconds(string $identity): int
    {
        if ($this->db === null || !$this->db->isAlive()) {
            return 0;
        }

        try {
            if (!$this->db->isSql()) {
                $row = $this->db->mongoFindOne('login_attempts', ['identity' => $identity]);
                if (!$row || empty($row['locked_until'])) {
                    return 0;
                }
                $remaining = strtotime((string)$row['locked_until']) - time();
                return max(0, $remaining);
            }

            $row = $this->db->fetch("SELECT locked_until FROM login_attempts WHERE identity = ?", [$identity]);
            if (!$row || empty($row['locked_until'])) {
                return 0;
            }

            $remaining = strtotime((string)$row['locked_until']) - time();
            return max(0, $remaining);
        } catch (\Throwable) {
            return 0;
        }
    }
}

