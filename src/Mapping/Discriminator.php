<?php

namespace MyBatis\Mapping;

class Discriminator
{
    public $resultMapping;
    public $discriminatorMap = [];

    public function getResultMapping(): ResultMapping
    {
        return $this->resultMapping;
    }

    public function getDiscriminatorMap(): array
    {
        return $this->discriminatorMap;
    }

    public function getMapIdFor(string $s): ?string
    {
        if (array_key_exists($s, $this->discriminatorMap)) {
            return $this->discriminatorMap[$s];
        }
        return null;
    }
}
