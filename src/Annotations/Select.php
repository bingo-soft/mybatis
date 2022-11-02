<?php

namespace MyBatis\Annotations;

#[Attribute(Attribute::TARGET_METHOD)]
#[Attribute(Attribute::IS_REPEATABLE)]
class Select
{
    public function __construct(private array $value, private string $databaseId = "")
    {
    }

    public function value(): array
    {
        return $this->value;
    }

    public function databaseId(): string
    {
        return $this->databaseId;
    }
}
