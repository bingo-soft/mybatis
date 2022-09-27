<?php

namespace MyBatis\Mapping;

use MyBatis\Reflection\Property\PropertyTokenizer;
use MyBatis\Session\Configuration;

class BoundSql
{
    private $sql;
    private $parameterMappings;
    private $parameterObject;
    private $additionalParameters;
    private $metaParameters;

    public function __construct(Configuration $configuration, string $sql, array $parameterMappings = [], $parameterObject = null)
    {
        $this->sql = $sql;
        $this->parameterMappings = $parameterMappings;
        $this->parameterObject = $parameterObject;
        $this->additionalParameters = [];
        $this->metaParameters = $configuration->newMetaObject($this->additionalParameters);
    }

    public function getSql(): string
    {
        return $this->sql;
    }

    public function getParameterMappings(): array
    {
        return $this->parameterMappings;
    }

    public function getParameterObject()
    {
        return $this->parameterObject;
    }

    public function hasAdditionalParameter(string $name): bool
    {
        $paramName = (new PropertyTokenizer($name))->getName();
        return array_key_exists($paramName, $this->additionalParameters);
    }

    public function setAdditionalParameter(string $name, $value): void
    {
        $this->metaParameters->setValue($name, $value);
    }

    public function getAdditionalParameter(string $name)
    {
        return $this->metaParameters->getValue($name);
    }

    public function getAdditionalParameters(): array
    {
        return $this->additionalParameters;
    }
}
