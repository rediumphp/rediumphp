<?php

namespace Redium\ORM;

use Redium\Database\QueryBuilder;
use Exception;

abstract class Repository
{
    protected string $entityClass;

    public function __construct(string $entityClass)
    {
        $this->entityClass = $entityClass;
    }

    /**
     * Find entity by primary key
     */
    public function find(mixed $id): ?Entity
    {
        $result = $this->query()->where($this->getPrimaryKey(), $id)->first();
        return $result ? ($this->entityClass)::fromDatabase($result) : null;
    }

    /**
     * Get all entities
     */
    public function all(): array
    {
        $results = $this->query()->get();
        return array_map(fn($row) => ($this->entityClass)::fromDatabase($row), $results);
    }

    /**
     * Save an entity (insert or update)
     */
    public function save(Entity $entity): bool
    {
        if (!($entity instanceof $this->entityClass)) {
            throw new Exception("Invalid entity type. Expected " . $this->entityClass);
        }
        return $entity->save();
    }

    /**
     * Delete an entity
     */
    public function delete(Entity $entity): bool
    {
        if (!($entity instanceof $this->entityClass)) {
            throw new Exception("Invalid entity type. Expected " . $this->entityClass);
        }
        return $entity->delete();
    }

    /**
     * Get a specialized QueryBuilder for this entity
     */
    protected function query(): QueryBuilder
    {
        return QueryBuilder::table(($this->entityClass)::getTableName());
    }

    /**
     * Helper to get primary key name
     */
    protected function getPrimaryKey(): string
    {
        return ($this->entityClass)::getPrimaryKey();
    }
}
