<?php

namespace MyBatis\Annotations;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Update
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
