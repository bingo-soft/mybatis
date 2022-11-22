<?php

namespace MyBatis\Annotations;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class SelectProvider
{
    public function __construct(private string $type = "void", private string $method = "", private string $databaseId = "")
    {
    }

    public function value(): string
    {
        return $this->type;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function method(): string
    {
        return $this->method;
    }

    public function databaseId(): string
    {
        return $this->databaseId;
    }
}
