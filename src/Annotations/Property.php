<?php

namespace MyBatis\Annotations;

use Attribute;

#[Attribute(Attribute::TARGET_ALL)]
class Property
{
    public function __construct(private string $name, private string $value)
    {
    }

    public function name(): string
    {
        return $this->name;
    }

    public function value(): string
    {
        return $this->value;
    }
}
