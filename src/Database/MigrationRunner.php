<?php
declare(strict_types=1);

namespace Nexus\Database;

use RuntimeException;

/**
 * Transactional Schema Migration Engine
 *
 * Discovers, tracks, and applies versioned schema migrations atomically.
 */
class MigrationRunner
{
    private Database $db;
    private string $migrationsPath;

    public function __construct(Database $db, ?string $migrationsPath = null)
    {
        $this->db = $db;
        $this->migrationsPath = $migrationsPath ?? dirname(__DIR__, 2) . '/database/migrations';
    }

    /**
     * Ensure the migrations tracking table exists
     */
    public function ensureMigrationTable(): void
    {
        $driver = $this->db->fetch("SELECT 1") !== null; // forces connect
        
        $sql = "CREATE TABLE IF NOT EXISTS migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL UNIQUE,
            batch INT NOT NULL,
            applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        // Check if SQLite
        try {
            $this->db->execute($sql);
        } catch (\Throwable) {
            // SQLite fallback syntax
            $sqliteSql = "CREATE TABLE IF NOT EXISTS migrations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                migration TEXT NOT NULL UNIQUE,
                batch INTEGER NOT NULL,
                applied_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );";
            $this->db->execute($sqliteSql);
        }
    }

    /**
     * Get list of all migration files sorted by name
     */
    public function getMigrationFiles(): array
    {
        if (!is_dir($this->migrationsPath)) {
            return [];
        }

        $files = glob($this->migrationsPath . '/*.sql');
        if ($files === false) {
            return [];
        }

        sort($files, SORT_NATURAL);
        return $files;
    }

    /**
     * Get list of applied migration names
     */
    public function getAppliedMigrations(): array
    {
        $this->ensureMigrationTable();

        $rows = $this->db->fetchAll("SELECT migration, batch, applied_at FROM migrations ORDER BY id ASC");
        $applied = [];
        foreach ($rows as $row) {
            $applied[$row['migration']] = [
                'batch' => (int)$row['batch'],
                'applied_at' => $row['applied_at'],
            ];
        }

        return $applied;
    }

    /**
     * Get current batch number
     */
    public function getNextBatchNumber(): int
    {
        $row = $this->db->fetch("SELECT MAX(batch) as max_batch FROM migrations");
        return ((int)($row['max_batch'] ?? 0)) + 1;
    }

    /**
     * Run all pending migrations atomically
     *
     * @return array List of applied migration names
     */
    public function migrate(): array
    {
        $this->ensureMigrationTable();

        $applied = $this->getAppliedMigrations();
        $files = $this->getMigrationFiles();
        $nextBatch = $this->getNextBatchNumber();
        $executed = [];

        foreach ($files as $file) {
            $filename = basename($file);

            if (isset($applied[$filename])) {
                continue; // Already applied
            }

            $sql = file_get_contents($file);
            if ($sql === false || trim($sql) === '') {
                continue;
            }

            // Execute migration statements
            $this->db->transaction(function (Database $db) use ($sql, $filename, $nextBatch): void {
                // Split multi-statement SQL if needed
                $statements = array_filter(
                    array_map('trim', explode(';', $sql)),
                    fn($s) => $s !== '' && !str_starts_with($s, '--')
                );

                foreach ($statements as $statement) {
                    $db->execute($statement);
                }

                $db->execute(
                    "INSERT INTO migrations (migration, batch, applied_at) VALUES (?, ?, CURRENT_TIMESTAMP)",
                    [$filename, $nextBatch]
                );
            });

            $executed[] = $filename;
        }

        return $executed;
    }

    /**
     * Rollback the last applied migration batch
     */
    public function rollback(): array
    {
        $this->ensureMigrationTable();

        $lastBatchRow = $this->db->fetch("SELECT MAX(batch) as last_batch FROM migrations");
        $lastBatch = (int)($lastBatchRow['last_batch'] ?? 0);

        if ($lastBatch === 0) {
            return [];
        }

        $migrations = $this->db->fetchAll(
            "SELECT migration FROM migrations WHERE batch = ? ORDER BY id DESC",
            [$lastBatch]
        );

        $rolledBack = [];
        foreach ($migrations as $row) {
            $this->db->execute(
                "DELETE FROM migrations WHERE migration = ? AND batch = ?",
                [$row['migration'], $lastBatch]
            );
            $rolledBack[] = $row['migration'];
        }

        return $rolledBack;
    }

    /**
     * Get detailed migration status report
     */
    public function status(): array
    {
        $this->ensureMigrationTable();

        $applied = $this->getAppliedMigrations();
        $files = $this->getMigrationFiles();
        $status = [];

        foreach ($files as $file) {
            $filename = basename($file);
            $isApplied = isset($applied[$filename]);

            $status[] = [
                'migration' => $filename,
                'applied' => $isApplied,
                'batch' => $isApplied ? $applied[$filename]['batch'] : null,
                'applied_at' => $isApplied ? $applied[$filename]['applied_at'] : null,
            ];
        }

        return $status;
    }
}

