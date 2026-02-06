<?php

namespace Redium\ORM;

use Redium\Database\Connection;
use Redium\Database\QueryBuilder;
use Redium\ORM\Attributes\Column;
use Redium\ORM\Attributes\Table;
use ReflectionClass;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

abstract class Entity
{
    protected static ?string $tableName = null;
    protected static string $primaryKeyName = 'id';
    
    protected bool $exists = false;
    private static ?Serializer $serializer = null;

    public function __construct(array $attributes = [])
    {
        if (!empty($attributes)) {
            $this->fill($attributes);
        }
    }

    /**
     * Get the serializer instance
     */
    protected static function getSerializer(): Serializer
    {
        if (self::$serializer === null) {
            $normalizers = [
                new DateTimeNormalizer(),
                new ObjectNormalizer(null, null, null, new ReflectionExtractor()),
                new ArrayDenormalizer()
            ];
            $encoders = [new JsonEncoder()];
            self::$serializer = new Serializer($normalizers, $encoders);
        }
        return self::$serializer;
    }

    /**
     * Get the table name for the entity
     */
    public static function getTableName(): string
    {
        if (static::$tableName !== null) {
            return static::$tableName;
        }

        $reflection = new ReflectionClass(static::class);
        $attributes = $reflection->getAttributes(Table::class);

        if (!empty($attributes)) {
            return $attributes[0]->newInstance()->getName();
        }

        // Fallback to snake_case of class name
        $className = $reflection->getShortName();
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $className));
    }

    /**
     * Get the primary key name
     */
    public static function getPrimaryKey(): string
    {
        $reflection = new ReflectionClass(static::class);
        foreach ($reflection->getProperties() as $property) {
            $attributes = $property->getAttributes(Column::class);
            if (!empty($attributes)) {
                $column = $attributes[0]->newInstance();
                if ($column->isPrimaryKey()) {
                    return $column->getName() ?? $property->getName();
                }
            }
        }
        return static::$primaryKeyName;
    }

    /**
     * Fill entity with data using Symfony Serializer
     */
    public function fill(array $attributes): self
    {
        $serializer = self::getSerializer();
        $serializer->denormalize($attributes, static::class, null, [
            AbstractNormalizer::OBJECT_TO_POPULATE => $this
        ]);
        return $this;
    }

    /**
     * Save entity to database
     */
    public function save(): bool
    {
        return $this->exists ? $this->update() : $this->insert();
    }

    /**
     * Insert new record
     */
    protected function insert(): bool
    {
        $data = $this->toArray(true);
        $primaryKey = static::getPrimaryKey();
        
        // Remove auto-increment primary key if not set
        if (isset($data[$primaryKey]) && empty($data[$primaryKey])) {
            unset($data[$primaryKey]);
        }
        
        $result = QueryBuilder::table(static::getTableName())->insert($data);
        
        if ($result) {
            $this->exists = true;
            $pdo = Connection::getPdo();
            $lastId = $pdo->lastInsertId();
            if ($lastId) {
                $this->setPrimaryKey($lastId);
            }
        }

        return $result;
    }

    /**
     * Update existing record
     */
    protected function update(): bool
    {
        $data = $this->toArray(true);
        $primaryKey = static::getPrimaryKey();
        $primaryKeyValue = $data[$primaryKey] ?? null;

        if (!$primaryKeyValue) {
            return false;
        }

        return QueryBuilder::table(static::getTableName())
            ->where($primaryKey, $primaryKeyValue)
            ->update($data);
    }

    /**
     * Delete entity from database
     */
    public function delete(): bool
    {
        if (!$this->exists) {
            return false;
        }

        $primaryKey = static::getPrimaryKey();
        $primaryKeyValue = $this->getPrimaryKeyValue();
        
        return QueryBuilder::table(static::getTableName())
            ->where($primaryKey, $primaryKeyValue)
            ->delete();
    }

    /**
     * Convert entity to array
     */
    public function toArray(bool $forDatabase = false): array
    {
        $serializer = self::getSerializer();
        $data = $serializer->normalize($this);

        if ($forDatabase) {
            return $this->mapToDatabaseColumns($data);
        }

        return $this->hideSensitiveAttributes($data);
    }

    /**
     * Map object properties to database columns
     */
    protected function mapToDatabaseColumns(array $data): array
    {
        $mapped = [];
        $reflection = new ReflectionClass(static::class);

        foreach ($reflection->getProperties() as $property) {
            $attributes = $property->getAttributes(Column::class);
            $propName = $property->getName();
            
            if (!empty($attributes)) {
                $column = $attributes[0]->newInstance();
                $columnName = $column->getName() ?? $propName;
                if (array_key_exists($propName, $data)) {
                    $mapped[$columnName] = $data[$propName];
                }
            } else {
                // If no attribute, assume property name is column name
                if (array_key_exists($propName, $data)) {
                    $mapped[$propName] = $data[$propName];
                }
            }
        }
        return $mapped;
    }

    /**
     * Hide sensitive attributes
     */
    protected function hideSensitiveAttributes(array $data): array
    {
        $reflection = new ReflectionClass(static::class);
        foreach ($reflection->getProperties() as $property) {
            $attributes = $property->getAttributes(Column::class);
            if (!empty($attributes)) {
                $column = $attributes[0]->newInstance();
                if ($column->isHidden()) {
                    unset($data[$property->getName()]);
                }
            }
        }
        return $data;
    }

    /**
     * Find entity by primary key
     */
    public static function find(mixed $id): ?static
    {
        $primaryKey = static::getPrimaryKey();
        $result = QueryBuilder::table(static::getTableName())
            ->where($primaryKey, $id)
            ->first();

        if (!$result) {
            return null;
        }

        return static::fromDatabase($result);
    }

    /**
     * Create entity from database row
     */
    public static function fromDatabase(array $row): static
    {
        $entity = new static();
        $mappedRow = static::mapFromDatabaseColumns($row);
        $entity->fill($mappedRow);
        $entity->exists = true;
        return $entity;
    }

    /**
     * Map database columns back to object properties
     */
    public static function mapFromDatabaseColumns(array $row): array
    {
        $mapped = [];
        $reflection = new ReflectionClass(static::class);

        foreach ($reflection->getProperties() as $property) {
            $attributes = $property->getAttributes(Column::class);
            $propName = $property->getName();
            
            if (!empty($attributes)) {
                $column = $attributes[0]->newInstance();
                $columnName = $column->getName() ?? $propName;
                if (array_key_exists($columnName, $row)) {
                    $mapped[$propName] = $row[$columnName];
                }
            } else {
                if (array_key_exists($propName, $row)) {
                    $mapped[$propName] = $row[$propName];
                }
            }
        }
        return $mapped;
    }

    /**
     * Find entity or throw exception
     */
    public static function findOrFail(mixed $id): static
    {
        $entity = static::find($id);
        if (!$entity) {
            throw new \Exception("Entity not found in " . static::getTableName() . " with ID {$id}");
        }
        return $entity;
    }

    /**
     * Get all entities
     */
    public static function all(): array
    {
        $results = QueryBuilder::table(static::getTableName())->get();
        return array_map(fn($row) => static::fromDatabase($row), $results);
    }

    /**
     * Create where query
     */
    public static function where(string $column, string $operator, mixed $value = null): QueryBuilder
    {
        return QueryBuilder::table(static::getTableName())->where($column, $operator, $value);
    }

    /**
     * Create and save entity
     */
    public static function create(array $attributes): static
    {
        $entity = new static($attributes);
        $entity->save();
        return $entity;
    }

    /**
     * Helper to set primary key value
     */
    protected function setPrimaryKey(mixed $value): void
    {
        $reflection = new ReflectionClass($this);
        $primaryKey = static::getPrimaryKey();

        foreach ($reflection->getProperties() as $property) {
            $attributes = $property->getAttributes(Column::class);
            if (!empty($attributes)) {
                $column = $attributes[0]->newInstance();
                if ($column->isPrimaryKey() || ($column->getName() === $primaryKey)) {
                    $property->setAccessible(true);
                    $property->setValue($this, $value);
                    return;
                }
            } elseif ($property->getName() === $primaryKey) {
                $property->setAccessible(true);
                $property->setValue($this, $value);
                return;
            }
        }
    }

    /**
     * Helper to get primary key value
     */
    protected function getPrimaryKeyValue(): mixed
    {
        $reflection = new ReflectionClass($this);
        $primaryKey = static::getPrimaryKey();

        foreach ($reflection->getProperties() as $property) {
            $attributes = $property->getAttributes(Column::class);
            if (!empty($attributes)) {
                $column = $attributes[0]->newInstance();
                if ($column->isPrimaryKey() || ($column->getName() === $primaryKey)) {
                    $property->setAccessible(true);
                    return $property->getValue($this);
                }
            } elseif ($property->getName() === $primaryKey) {
                $property->setAccessible(true);
                return $property->getValue($this);
            }
        }
        return null;
    }
}
