<?php

namespace MyBatis\Annotations;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class ResultMap
{
    public function __construct(private array $value)
    {
    }

    public function value(): array
    {
        return $this->value;
    }
}
