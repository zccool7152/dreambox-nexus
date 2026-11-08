<?php
declare(strict_types=1);

namespace Nexus\Database;

use InvalidArgumentException;

/**
 * Fluent SQL Query Builder
 *
 * Guarantees 100% parameterization while eliminating raw SQL string concatenation.
 */
class QueryBuilder
{
    private Database $db;
    private string $table;
    private array $columns = ['*'];
    private array $wheres = [];
    private array $params = [];
    private ?string $orderBy = null;
    private ?int $limit = null;
    private ?int $offset = null;

    public function __construct(Database $db, string $table)
    {
        $this->db = $db;
        $this->table = $this->db->assertIdentifier($table);
    }

    public function select(array|string $columns = ['*']): static
    {
        $cols = is_array($columns) ? $columns : func_get_args();
        $this->columns = array_map(function ($col) {
            return $col === '*' ? '*' : $this->db->assertIdentifier($col);
        }, $cols);

        return $this;
    }

    public function where(string $column, string $operator, mixed $value): static
    {
        $quotedCol = $this->db->assertIdentifier($column);
        $allowedOps = ['=', '!=', '<>', '<', '<=', '>', '>=', 'LIKE', 'NOT LIKE'];

        if (!in_array(strtoupper($operator), $allowedOps, true)) {
            throw new InvalidArgumentException("Unsupported SQL operator: '{$operator}'");
        }

        $this->wheres[] = "{$quotedCol} {$operator} ?";
        $this->params[] = $value;
        return $this;
    }

    public function whereIn(string $column, array $values): static
    {
        if (empty($values)) {
            $this->wheres[] = '0 = 1';
            return $this;
        }

        $quotedCol = $this->db->assertIdentifier($column);
        $placeholders = implode(', ', array_fill(0, count($values), '?'));

        $this->wheres[] = "{$quotedCol} IN ({$placeholders})";
        foreach ($values as $val) {
            $this->params[] = $val;
        }

        return $this;
    }

    public function whereNull(string $column): static
    {
        $quotedCol = $this->db->assertIdentifier($column);
        $this->wheres[] = "{$quotedCol} IS NULL";
        return $this;
    }

    public function whereNotNull(string $column): static
    {
        $quotedCol = $this->db->assertIdentifier($column);
        $this->wheres[] = "{$quotedCol} IS NOT NULL";
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): static
    {
        $quotedCol = $this->db->assertIdentifier($column);
        $dir = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $this->orderBy = "{$quotedCol} {$dir}";
        return $this;
    }

    public function limit(int $limit, ?int $offset = null): static
    {
        $this->limit = max(1, $limit);
        if ($offset !== null) {
            $this->offset = max(0, $offset);
        }
        return $this;
    }

    public function get(): array
    {
        $sql = "SELECT " . implode(', ', $this->columns) . " FROM {$this->table}";

        if (!empty($this->wheres)) {
            $sql .= " WHERE " . implode(' AND ', $this->wheres);
        }

        if ($this->orderBy !== null) {
            $sql .= " ORDER BY {$this->orderBy}";
        }

        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
            if ($this->offset !== null) {
                $sql .= " OFFSET {$this->offset}";
            }
        }

        return $this->db->fetchAll($sql, $this->params);
    }

    public function first(): ?array
    {
        $this->limit(1);
        $rows = $this->get();
        return $rows[0] ?? null;
    }

    public function insert(array $data): string
    {
        if (empty($data)) {
            throw new InvalidArgumentException("Cannot insert empty data array.");
        }

        $columns = [];
        $placeholders = [];
        $values = [];

        foreach ($data as $col => $val) {
            $columns[] = $this->db->assertIdentifier((string)$col);
            $placeholders[] = '?';
            $values[] = $val;
        }

        $sql = "INSERT INTO {$this->table} (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $this->db->execute($sql, $values);

        return $this->db->lastInsertId();
    }

    public function update(array $data): int
    {
        if (empty($data)) {
            throw new InvalidArgumentException("Cannot update empty data array.");
        }

        $sets = [];
        $values = [];

        foreach ($data as $col => $val) {
            $sets[] = $this->db->assertIdentifier((string)$col) . " = ?";
            $values[] = $val;
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $sets);

        if (!empty($this->wheres)) {
            $sql .= " WHERE " . implode(' AND ', $this->wheres);
        }

        $allParams = array_merge($values, $this->params);
        return $this->db->execute($sql, $allParams);
    }

    public function delete(): int
    {
        $sql = "DELETE FROM {$this->table}";

        if (!empty($this->wheres)) {
            $sql .= " WHERE " . implode(' AND ', $this->wheres);
        }

        return $this->db->execute($sql, $this->params);
    }
}

