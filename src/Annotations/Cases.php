<?php

namespace MyBatis\Annotations;

#[Attribute(Attribute::TARGET_ALL)]
class Cases
{
    public function __construct(private string $value, private string $type, private array $results = [], private array $constructArgs = [])
    {
    }

    public function value(): string
    {
        return $this->value;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function results(): array
    {
        return $this->results;
    }

    public function constructArgs(): array
    {
        return $this->constructArgs;
    }
}
