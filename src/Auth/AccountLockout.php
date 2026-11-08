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

        $row = $this->db->fetch(
            "SELECT attempts, locked_until FROM login_attempts WHERE identity = ?",
            [$identity]
        );

        if (!$row) {
            return false;
        }

        if ($row['locked_until'] !== null) {
            $lockedUntil = strtotime($row['locked_until']);
            if ($lockedUntil > time()) {
                return true;
            }
        }

        return false;
    }

    public function recordFailedAttempt(string $identity, string $ip): void
    {
        if ($this->db === null || !$this->db->isAlive()) {
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
    }

    public function resetAttempts(string $identity): void
    {
        if ($this->db === null || !$this->db->isAlive()) {
            return;
        }

        $this->db->execute("DELETE FROM login_attempts WHERE identity = ?", [$identity]);
    }

    public function getLockoutRemainingSeconds(string $identity): int
    {
        if ($this->db === null || !$this->db->isAlive()) {
            return 0;
        }

        $row = $this->db->fetch("SELECT locked_until FROM login_attempts WHERE identity = ?", [$identity]);
        if (!$row || $row['locked_until'] === null) {
            return 0;
        }

        $remaining = strtotime($row['locked_until']) - time();
        return max(0, $remaining);
    }
}

