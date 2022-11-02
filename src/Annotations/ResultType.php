<?php

namespace MyBatis\Annotations;

#[Attribute(Attribute::TARGET_METHOD)]
class ResultType
{
    public function __construct(private string $value)
    {
    }

    public function value(): string
    {
        return $this->value;
    }
}
