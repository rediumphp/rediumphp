<?php

namespace Redium\ORM\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Column
{
    public function __construct(
        private readonly ?string $name = null,
        private readonly int $length = 255,
        private readonly bool $nullable = true,
        private readonly bool $unique = false,
        private readonly ?string $columnType = "text" | "json"
    ) {}

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getLength(): int
    {
        return $this->length;
    }

    public function isNullable(): bool
    {
        return $this->nullable;
    }

    public function isUnique(): bool
    {
        return $this->unique;
    }

    public function getColumnType(): ?string
    {
        return $this->columnType;
    }

}
