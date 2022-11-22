<?php

namespace MyBatis\Annotations;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Select
{
    public function __construct(private $value, private string $databaseId = "")
    {
    }

    public function value(): array
    {
        return is_array($this->value) ? $this->value : [ $this->value ];
    }

    public function databaseId(): string
    {
        return $this->databaseId;
    }
}
