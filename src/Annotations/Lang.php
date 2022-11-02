<?php

namespace MyBatis\Annotations;

#[Attribute(Attribute::TARGET_METHOD)]
class Lang
{
    public function __construct(private string $value)
    {
    }

    public function value(): string
    {
        return $this->value;
    }
}
