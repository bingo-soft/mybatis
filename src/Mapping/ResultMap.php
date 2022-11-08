<?php

namespace MyBatis\Mapping;

use MyBatis\Annotations\Param;
use MyBatis\Builder\BuilderException;
use MyBatis\Session\Configuration;

class ResultMap
{
    public $configuration;

    public $id;
    public $type;
    public $resultMappings = [];
    public $idResultMappings = [];
    public $constructorResultMappings = [];
    public $propertyResultMappings = [];
    public $mappedColumns = [];
    public $mappedProperties = [];
    public $discriminator;
    public $hasNestedResultMaps;
    public $hasNestedQueries;
    public $autoMapping;

    public function __construct()
    {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function hasNestedResultMaps(): bool
    {
        return $this->hasNestedResultMaps;
    }

    public function hasNestedQueries(): bool
    {
        return $this->hasNestedQueries;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getResultMappings(): array
    {
        return $this->resultMappings;
    }

    public function getConstructorResultMappings(): array
    {
        return $this->constructorResultMappings;
    }

    public function getPropertyResultMappings(): array
    {
        return $this->propertyResultMappings;
    }

    public function getIdResultMappings(): array
    {
        return $this->idResultMappings;
    }

    public function getMappedColumns(): array
    {
        return $this->mappedColumns;
    }

    public function getMappedProperties(): array
    {
        return $this->mappedProperties;
    }

    public function getDiscriminator(): Discriminator
    {
        return $this->discriminator;
    }

    public function forceNestedResultMaps(): void
    {
        $this->hasNestedResultMaps = true;
    }

    public function getAutoMapping(): bool
    {
        return $this->autoMapping;
    }
}
