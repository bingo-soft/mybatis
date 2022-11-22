<?php

namespace MyBatis\Annotations;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
class Param
{
    public function __construct(private string $value)
    {
    }

    public function value(): string
    {
        return $this->value;
    }
}
