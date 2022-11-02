<?php

namespace MyBatis\Annotations;

#[Attribute(Attribute::TARGET_METHOD)]
class ConstructorArgs
{
    public function __construct(private array $value = [])
    {
    }

    public function value(): array
    {
        return $this->value;
    }
}
