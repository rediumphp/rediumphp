<?php

namespace Redium\Database;

class QueryBuilder
{
    private \PDO $connection;
    private string $table;
    private array $wheres = [];
    private array $bindings = [];
    private ?string $orderBy = null;
    private ?int $limit = null;
    private ?int $offset = null;
    private array $selects = ['*'];

    public function __construct(string $table)
    {
        $this->connection = Connection::getPdo();
        $this->table = $table;
    }

    /**
     * Select specific columns
     */
    public function select(string ...$columns): self
    {
        $this->selects = $columns;
        return $this;
    }

    /**
     * Add WHERE clause
     */
    public function where(string $column, string $operator, mixed $value = null): self
    {
        // If only 2 params, assume '=' operator
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = "{$column} {$operator} ?";
        $this->bindings[] = $value;
        return $this;
    }

    /**
     * Add WHERE IN clause
     */
    public function whereIn(string $column, array $values): self
    {
        $placeholders = implode(',', array_fill(0, count($values), '?'));
        $this->wheres[] = "{$column} IN ({$placeholders})";
        $this->bindings = array_merge($this->bindings, $values);
        return $this;
    }

    /**
     * Add WHERE LIKE clause
     */
    public function whereLike(string $column, string $value): self
    {
        $this->wheres[] = "{$column} LIKE ?";
        $this->bindings[] = $value;
        return $this;
    }

    /**
     * Add ORDER BY clause
     */
    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->orderBy = "{$column} {$direction}";
        return $this;
    }

    /**
     * Add LIMIT clause
     */
    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Add OFFSET clause
     */
    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * Execute and get all results
     */
    public function get(): array
    {
        $sql = $this->buildSelectQuery();
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($this->bindings);
        return Connection::fetchAll($stmt);
    }

    /**
     * Execute and get first result
     */
    public function first(): ?array
    {
        $this->limit(1);
        $sql = $this->buildSelectQuery();
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($this->bindings);
        return Connection::fetch($stmt);
    }

    /**
     * Get count of results
     */
    public function count(): int
    {
        $originalSelects = $this->selects;
        $this->selects = ['COUNT(*) as count'];
        
        $sql = $this->buildSelectQuery();
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($this->bindings);
        $result = Connection::fetch($stmt);
        
        $this->selects = $originalSelects;
        return (int) ($result['count'] ?? 0);
    }

    /**
     * Build SELECT query
     */
    private function buildSelectQuery(): string
    {
        $columns = implode(', ', $this->selects);
        $sql = "SELECT {$columns} FROM {$this->table}";

        if (!empty($this->wheres)) {
            $sql .= " WHERE " . implode(' AND ', $this->wheres);
        }

        if ($this->orderBy) {
            $sql .= " ORDER BY {$this->orderBy}";
        }

        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
        }

        if ($this->offset !== null) {
            $sql .= " OFFSET {$this->offset}";
        }

        return $sql;
    }

    /**
     * Insert data
     */
    public function insert(array $data): bool
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        $stmt = $this->connection->prepare($sql);
        
        return $stmt->execute(array_values($data));
    }

    /**
     * Update data
     */
    public function update(array $data): bool
    {
        $sets = [];
        $values = [];

        foreach ($data as $column => $value) {
            $sets[] = "{$column} = ?";
            $values[] = $value;
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $sets);

        if (!empty($this->wheres)) {
            $sql .= " WHERE " . implode(' AND ', $this->wheres);
            $values = array_merge($values, $this->bindings);
        }

        $stmt = $this->connection->prepare($sql);
        return $stmt->execute($values);
    }

    /**
     * Delete records
     */
    public function delete(): bool
    {
        $sql = "DELETE FROM {$this->table}";

        if (!empty($this->wheres)) {
            $sql .= " WHERE " . implode(' AND ', $this->wheres);
        }

        $stmt = $this->connection->prepare($sql);
        return $stmt->execute($this->bindings);
    }

    /**
     * Static constructor
     */
    public static function table(string $table): self
    {
        return new self($table);
    }
}
