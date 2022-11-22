<?php

namespace MyBatis\Annotations;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class MapKey
{
    public function __construct(private string $value)
    {
    }

    public function value(): string
    {
        return $this->value;
    }
}
