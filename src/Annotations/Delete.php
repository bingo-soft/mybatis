<?php

namespace MyBatis\Annotations;

#[Attribute(Attribute::TARGET_METHOD)]
class Delete
{
    public function __construct(private string $value, private string $databaseId = "")
    {
    }

    public function value(): string
    {
        return $this->value;
    }

    public function databaseId(): string
    {
        return $this->databaseId;
    }
}
