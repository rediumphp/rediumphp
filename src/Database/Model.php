<?php

namespace Redium\Database;

use PDO;

abstract class Model implements ModelInterface
{
    protected static PDO $connection;
    protected string $table;
    protected string $primaryKey = 'id';

    public function __construct()
    {
        self::$connection = Connection::getInstance();
    }

    /**
     * Save a new record to the database
     */
    public function save(array $data): void
    {
        $fields = array_keys($data);
        $placeholders = array_fill(0, count($fields), '?');
        
        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            $this->table,
            implode(', ', $fields),
            implode(', ', $placeholders)
        );

        $stmt = self::$connection->prepare($sql);
        $stmt->execute(array_values($data));
    }

    /**
     * Find all records with optional pagination
     */
    public function findAll(string $view = "*", int $size = 10, int $page = 0): array
    {
        $offset = $this->getPagingOffset($page, $size);
        $sql = "SELECT {$view} FROM {$this->table} ORDER BY {$this->primaryKey} DESC LIMIT {$size} OFFSET {$offset}";
        
        $result = self::$connection->query($sql);
        
        return [
            'data' => Connection::fetchAll($result),
            'size' => $size,
            'page' => $page,
            'total' => $this->count()
        ];
    }

    /**
     * Find a single record by ID
     */
    public function findById(int $id, string $view = "*"): ?array
    {
        $sql = "SELECT {$view} FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $stmt = self::$connection->prepare($sql);
        $stmt->execute([$id]);
        
        return Connection::fetch($stmt);
    }

    /**
     * Find a single record by field value
     */
    public function findBy(string $field, int|string $value, string $view = "*"): ?array
    {
        $sql = "SELECT {$view} FROM {$this->table} WHERE {$field} = ?";
        $stmt = self::$connection->prepare($sql);
        $stmt->execute([$value]);
        
        return Connection::fetch($stmt);
    }

    /**
     * Find all records matching field value
     */
    public function findAllBy(string $field, int|string $value, string $view = "*", int $size = 10, int $page = 0): array
    {
        $offset = $this->getPagingOffset($page, $size);
        $sql = "SELECT {$view} FROM {$this->table} WHERE {$field} = ? LIMIT {$size} OFFSET {$offset}";
        $stmt = self::$connection->prepare($sql);
        $stmt->execute([$value]);
        
        return Connection::fetchAll($stmt);
    }

    /**
     * Count total records in table
     */
    public function count(): int
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        $result = self::$connection->query($sql);
        $row = Connection::fetch($result);
        
        return (int) $row['total'];
    }

    /**
     * Count records matching field value
     */
    public function countBy(string $field, int|string $value): int
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE {$field} = ?";
        $stmt = self::$connection->prepare($sql);
        $stmt->execute([$value]);
        $row = Connection::fetch($stmt);
        
        return (int) $row['total'];
    }

    /**
     * Update an existing record
     * Expects $data to contain the primary key
     */
    public function update(array $data): void
    {
        if (!isset($data[$this->primaryKey])) {
            throw new \InvalidArgumentException("Primary key '{$this->primaryKey}' must be present in data array");
        }

        $id = $data[$this->primaryKey];
        unset($data[$this->primaryKey]);

        $fields = array_keys($data);
        $setClause = implode(' = ?, ', $fields) . ' = ?';

        $sql = "UPDATE {$this->table} SET {$setClause} WHERE {$this->primaryKey} = ?";
        $values = array_merge(array_values($data), [$id]);

        $stmt = self::$connection->prepare($sql);
        $stmt->execute($values);
    }

    /**
     * Update a specific field for a record identified by identifier
     */
    public function updateBy(string $identifier, string $field, int|string|null $value): void
    {
        $sql = "UPDATE {$this->table} SET {$field} = ? WHERE {$this->primaryKey} = ?";
        $stmt = self::$connection->prepare($sql);
        $stmt->execute([$value, $identifier]);
    }

    /**
     * Delete a record by ID
     */
    public function delete(int $id): void
    {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $stmt = self::$connection->prepare($sql);
        $stmt->execute([$id]);
    }

    /**
     * Execute a raw query
     */
    protected function query(string $sql): \PDOStatement
    {
        return self::$connection->query($sql);
    }

    /**
     * Prepare a statement
     */
    protected function prepare(string $sql): \PDOStatement
    {
        return self::$connection->prepare($sql);
    }

    /**
     * Calculate pagination offset
     */
    private function getPagingOffset(int $page, int $size): int
    {
        return (int) ceil((($page + 1) - 1) * $size);
    }
}
