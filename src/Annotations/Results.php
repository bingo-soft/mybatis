<?php

namespace MyBatis\Annotations;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Results
{
    public function __construct(private mixed $value = [], private string $id = "")
    {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function value(): mixed
    {
        return $this->value;
    }
}
