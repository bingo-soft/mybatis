<?php

namespace MyBatis\Annotations;

#[Attribute(Attribute::TARGET_METHOD)]
#[Attribute(Attribute::IS_REPEATABLE)]
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
