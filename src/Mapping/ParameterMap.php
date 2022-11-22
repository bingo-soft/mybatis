<?php

namespace MyBatis\Mapping;

use MyBatis\Session\Configuration;

class ParameterMap
{
    private $id;
    private $type;
    private $parameterMappings = [];

    public function __construct()
    {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getParameterMappings(): array
    {
        return $this->parameterMappings;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    public function setParameterMappings(array $parameterMappings): void
    {
        $this->parameterMappings = $parameterMappings;
    }
}
