<?php

namespace MyBatis\Annotations;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class CacheNamespace
{
    public function __construct(
        private string $name = ""
    ) {
    }

    public function name(): string
    {
        return $this->name;
    }
}
