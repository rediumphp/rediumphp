<?php

namespace Redium\Database;

interface ModelInterface
{
    /**
     * Save a new record
     */
    public function save(array $data): void;

    /**
     * Find all records with pagination
     */
    public function findAll(string $view = "*", int $size = 10, int $page = 0): array;

    /**
     * Find record by ID
     */
    public function findById(int $id, string $view = "*"): ?array;

    /**
     * Find single record by field value
     */
    public function findBy(string $field, int|string $value, string $view = "*"): ?array;

    /**
     * Find all records by field value
     */
    public function findAllBy(string $field, int|string $value, string $view = "*", int $size = 10, int $page = 0): array;

    /**
     * Count total records
     */
    public function count(): int;

    /**
     * Count records by field value
     */
    public function countBy(string $field, int|string $value): int;

    /**
     * Update existing record
     */
    public function update(array $data): void;

    /**
     * Update specific field for a record
     */
    public function updateBy(string $identifier, string $field, int|string|null $value): void;
}
