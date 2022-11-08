<?php

namespace MyBatis\Builder\Annotation;

class ProviderContext
{
    private $mapperType;
    private $mapperMethod;
    private $databaseId;

    public function __construct(string $mapperType, \ReflectionMethod $mapperMethod, string $databaseId)
    {
        $this->mapperType = $mapperType;
        $this->mapperMethod = $mapperMethod;
        $this->databaseId = $databaseId;
    }

    public function getMapperType(): string
    {
        return $this->mapperType;
    }

    public function getMapperMethod(): \ReflectionMethod
    {
        return $this->mapperMethod;
    }

    public function getDatabaseId(): string
    {
        return $this->databaseId;
    }
}
