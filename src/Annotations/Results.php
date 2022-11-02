<?php

namespace MyBatis\Annotations;

#[Attribute(Attribute::TARGET_METHOD)]
class Results
{
    public function __construct(private string $id = "", private array $value = [])
    {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function value(): array
    {
        return $this->value;
    }
}
