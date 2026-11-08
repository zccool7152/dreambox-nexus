<?php
declare(strict_types=1);

namespace Nexus\Database;

use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;
use InvalidArgumentException;

/**
 * Hardened Database Access Layer
 *
 * Enforces zero-emulation prepared statements, UTF-8 MB4 collation,
 * nested transaction savepoints, dynamic identifier whitelisting,
 * and native MongoDB document store connectivity.
 */
class Database
{
    private ?PDO $pdo = null;
    private mixed $mongoManager = null;
    private array $config;
    private int $transactionLevel = 0;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public function getDriver(): string
    {
        return (string)($this->config['driver'] ?? 'mysql');
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function isSql(): bool
    {
        return in_array($this->getDriver(), ['mysql', 'sqlite', 'mariadb', 'pgsql'], true);
    }

    /**
     * Get or initialize the PDO connection
     */
    public function getPdo(): PDO
    {
        if ($this->getDriver() === 'mongodb') {
            throw new RuntimeException("Current database driver is 'mongodb'. Use getMongoManager() or mongo* methods.");
        }

        if ($this->pdo === null) {
            $this->connect();
        }

        return $this->pdo;
    }

    /**
     * Establish the hardened database connection
     */
    public function connect(): void
    {
        $driver = $this->getDriver();

        if ($driver === 'mongodb') {
            $this->connectMongo();
            return;
        }

        if ($this->pdo !== null) {
            return;
        }

        try {
            if ($driver === 'sqlite') {
                $dbPath = $this->config['database'] ?? ':memory:';
                $dsn = "sqlite:{$dbPath}";
                $this->pdo = new PDO($dsn, null, null, $this->config['options'] ?? [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
                $this->pdo->exec('PRAGMA foreign_keys = ON;');
            } else {
                $host = $this->config['host'] ?? '127.0.0.1';
                $port = $this->config['port'] ?? 3306;
                $database = $this->config['database'] ?? 'dreambox_nexus';
                $charset = $this->config['charset'] ?? 'utf8mb4';

                $dsn = "mysql:host={$host};port={$port};dbname={$database};charset={$charset}";

                $options = $this->config['options'] ?? [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_TIMEOUT => 5,
                ];

                $this->pdo = new PDO(
                    $dsn,
                    $this->config['username'] ?? 'root',
                    $this->config['password'] ?? '',
                    $options
                );

                $collation = $this->config['collation'] ?? 'utf8mb4_unicode_ci';
                $this->pdo->exec("SET NAMES {$charset} COLLATE {$collation}");
            }
        } catch (PDOException $e) {
            // Mask raw connection credentials from exception trace
            throw new RuntimeException("Database connection failure: " . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    /**
     * Execute a parameterized query and return the PDOStatement
     */
    public function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Fetch a single row as an associative array
     */
    public function fetch(string $sql, array $params = []): ?array
    {
        $stmt = $this->query($sql, $params);
        $result = $stmt->fetch();
        return $result === false ? null : $result;
    }

    /**
     * Fetch all rows matching the query
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Execute an INSERT, UPDATE, or DELETE query and return affected row count
     */
    public function execute(string $sql, array $params = []): int
    {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    /**
     * Get the last inserted row ID
     */
    public function lastInsertId(?string $name = null): string
    {
        return $this->getPdo()->lastInsertId($name);
    }

    /**
     * Begin a transaction or create a nested savepoint
     */
    public function beginTransaction(): bool
    {
        $pdo = $this->getPdo();

        if ($this->transactionLevel === 0) {
            $result = $pdo->beginTransaction();
            $this->transactionLevel = 1;
            return $result;
        }

        $this->transactionLevel++;
        $pdo->exec("SAVEPOINT LEVEL{$this->transactionLevel}");
        return true;
    }

    /**
     * Commit the active transaction or release the nested savepoint
     */
    public function commit(): bool
    {
        $pdo = $this->getPdo();

        if ($this->transactionLevel === 0) {
            throw new RuntimeException("No active transaction to commit.");
        }

        if ($this->transactionLevel === 1) {
            $this->transactionLevel = 0;
            return $pdo->commit();
        }

        $pdo->exec("RELEASE SAVEPOINT LEVEL{$this->transactionLevel}");
        $this->transactionLevel--;
        return true;
    }

    /**
     * Rollback the active transaction or rollback to the nested savepoint
     */
    public function rollBack(): bool
    {
        $pdo = $this->getPdo();

        if ($this->transactionLevel === 0) {
            throw new RuntimeException("No active transaction to roll back.");
        }

        if ($this->transactionLevel === 1) {
            $this->transactionLevel = 0;
            return $pdo->rollBack();
        }

        $pdo->exec("ROLLBACK TO SAVEPOINT LEVEL{$this->transactionLevel}");
        $this->transactionLevel--;
        return true;
    }

    /**
     * Execute a callback inside an atomic transaction with automatic rollback
     */
    public function transaction(callable $callback): mixed
    {
        $this->beginTransaction();

        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->rollBack();
            throw $e;
        }
    }

    /**
     * Validate and quote a dynamic SQL identifier against a strict whitelist
     */
    public function assertIdentifier(string $identifier, array $whitelist = []): string
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $identifier)) {
            throw new InvalidArgumentException("Illegal characters detected in SQL identifier: '{$identifier}'");
        }

        if (!empty($whitelist) && !in_array($identifier, $whitelist, true)) {
            throw new InvalidArgumentException("Identifier '{$identifier}' is not in the allowed whitelist.");
        }

        $driver = $this->config['driver'] ?? 'mysql';
        return $driver === 'sqlite' ? "\"{$identifier}\"" : "`{$identifier}`";
    }

    /**
     * Check if the database connection is currently alive
     */
    public function isAlive(): bool
    {
        try {
            if ($this->getDriver() === 'mongodb') {
                return $this->pingMongo();
            }
            $this->getPdo()->query('SELECT 1');
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    // ── Native MongoDB Driver Subsystem ──────────────────────────────────────────

    public function getMongoManager(): mixed
    {
        if ($this->mongoManager === null) {
            $this->connectMongo();
        }

        return $this->mongoManager;
    }

    public function connectMongo(): void
    {
        if ($this->mongoManager !== null) {
            return;
        }

        if (!extension_loaded('mongodb')) {
            throw new RuntimeException("PHP 'mongodb' extension is not loaded.");
        }

        $host = $this->config['host'] ?? '127.0.0.1';
        $port = (int)($this->config['port'] ?? 27017);
        $db = $this->config['database'] ?? 'dreambox_nexus';
        $user = rawurlencode((string)($this->config['username'] ?? ''));
        $pass = rawurlencode((string)($this->config['password'] ?? ''));
        $authSource = $this->config['auth_source'] ?? $db;

        if ($user !== '') {
            $dsn = "mongodb://{$user}:{$pass}@{$host}:{$port}/{$db}?authSource={$authSource}";
        } else {
            $dsn = "mongodb://{$host}:{$port}/{$db}";
        }

        try {
            $managerClass = '\\MongoDB\\Driver\\Manager';
            $this->mongoManager = new $managerClass($dsn, [
                'serverSelectionTimeoutMS' => 3000,
                'connectTimeoutMS' => 3000,
            ]);
        } catch (\Throwable $e) {
            throw new RuntimeException("MongoDB connection failure: " . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    public function pingMongo(): bool
    {
        try {
            $manager = $this->getMongoManager();
            $db = $this->config['database'] ?? 'dreambox_nexus';
            $cmdClass = '\\MongoDB\\Driver\\Command';
            $command = new $cmdClass(['ping' => 1]);
            $cursor = $manager->executeCommand($db, $command);
            $res = current($cursor->toArray());
            return !empty($res->ok);
        } catch (\Throwable) {
            return false;
        }
    }

    public function mongoCommand(array $command): array
    {
        $manager = $this->getMongoManager();
        $db = $this->config['database'] ?? 'dreambox_nexus';
        $cmdClass = '\\MongoDB\\Driver\\Command';
        $cmd = new $cmdClass($command);
        $cursor = $manager->executeCommand($db, $cmd);
        return $cursor->toArray();
    }

    public function mongoInsert(string $collection, array $document): string
    {
        $manager = $this->getMongoManager();
        $db = $this->config['database'] ?? 'dreambox_nexus';
        $bulkClass = '\\MongoDB\\Driver\\BulkWrite';
        $bulk = new $bulkClass();
        $oidClass = '\\MongoDB\\BSON\\ObjectId';
        $id = $document['_id'] ?? new $oidClass();
        $document['_id'] = $id;
        $bulk->insert($document);
        $manager->executeBulkWrite("{$db}.{$collection}", $bulk);
        return (string)$id;
    }

    public function mongoFind(string $collection, array $filter = [], array $options = []): array
    {
        $manager = $this->getMongoManager();
        $db = $this->config['database'] ?? 'dreambox_nexus';
        $queryClass = '\\MongoDB\\Driver\\Query';
        $query = new $queryClass($filter, $options);
        $cursor = $manager->executeQuery("{$db}.{$collection}", $query);
        $results = [];
        foreach ($cursor as $doc) {
            $results[] = (array)$doc;
        }
        return $results;
    }

    public function mongoCount(string $collection, array $filter = []): int
    {
        $res = $this->mongoCommand(['count' => $collection, 'query' => (object)$filter]);
        return (int)($res[0]->n ?? 0);
    }

    public function mongoDelete(string $collection, array $filter = []): int
    {
        $manager = $this->getMongoManager();
        $db = $this->config['database'] ?? 'dreambox_nexus';
        $bulkClass = '\\MongoDB\\Driver\\BulkWrite';
        $bulk = new $bulkClass();
        $bulk->delete($filter);
        $result = $manager->executeBulkWrite("{$db}.{$collection}", $bulk);
        return $result->getDeletedCount() ?? 0;
    }
}
