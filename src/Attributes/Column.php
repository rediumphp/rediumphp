<?php

namespace Redium\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Column
{
    public function __construct(
        private ?string $name = null,
        private ?string $type = null,
        private bool $primaryKey = false,
        private bool $autoIncrement = false,
        private bool $hidden = false
    ) {}

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function isPrimaryKey(): bool
    {
        return $this->primaryKey;
    }

    public function isAutoIncrement(): bool
    {
        return $this->autoIncrement;
    }

    public function isHidden(): bool
    {
        return $this->hidden;
    }
}
