<?php

namespace Redium\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Table
{
    public function __construct(
        private string $name
    ) {}

    public function getName(): string
    {
        return $this->name;
    }
}
