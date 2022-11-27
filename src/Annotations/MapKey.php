<?php

namespace MyBatis\Annotations;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class MapKey
{
    public function __construct(private string $id = "id", private ?string $phpType = null)
    {
    }

    public function value(): string
    {
        return $this->id;
    }

    public function phpType(): ?string
    {
        return $this->phpType;
    }
}
