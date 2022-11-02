<?php

namespace MyBatis\Mapping;

use MyBatis\Session\Configuration;

class ParameterMapBuilder
{
    private $parameterMap;

    public function __construct(Configuration $configuration, string $id, string $type, array $parameterMappings)
    {
        $this->parameterMap = new ParameterMap();
        $this->parameterMap->setId($id);
        $this->parameterMap->setType($type);
        $this->parameterMap->setParameterMappings($parameterMappings);
    }

    public function type(): string
    {
        return $this->parameterMap->getType();
    }

    public function build(): ParameterMap
    {
        return $this->parameterMap;
    }
}
