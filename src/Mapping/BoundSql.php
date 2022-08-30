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

    public function __construct(Configuration $configuration, string $sql, array $parameterMappings = [], $parameterObject = null)
    {
        $this->sql = $sql;
        $this->parameterMappings = $parameterMappings;
        $this->parameterObject = $parameterObject;
        $this->additionalParameters = [];
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
        $this->additionalParameters[$name] = $value;
    }

    public function getAdditionalParameter(string $name)
    {
        if (array_key_exists($name, $this->additionalParameters)) {
            return $this->additionalParameters[$name];
        }
        return null;
    }
}
