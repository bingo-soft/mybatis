<?php

namespace MyBatis\Mapping;

use Doctrine\DBAL\Result;
use MyBatis\Session\Configuration;
use MyBatis\Type\{
    DbalType,
    TypeHandlerInterface,
    TypeHandlerRegistry
};

class ParameterMappingBuilder
{
    private $parameterMapping;

    public function __construct(Configuration $configuration, string $property, /*TypeHandlerInterface | string*/$typeHandlerOrType)
    {
        $this->parameterMapping = new ParameterMapping();
        $this->parameterMapping->configuration = $configuration;
        $this->parameterMapping->property = $property;
        if ($typeHandlerOrType instanceof TypeHandlerInterface) {
            $this->parameterMapping->typeHandler = $typeHandlerOrType;
        } elseif (is_string($typeHandlerOrType)) {
            $this->parameterMapping->phpType = $typeHandlerOrType;
        }
        $this->parameterMapping->mode = ParameterMode::IN;
    }

    public function mode(string $mode): ParameterMappingBuilder
    {
        $this->parameterMapping->mode = $mode;
        return $this;
    }

    public function phpType(string $phpType): ParameterMappingBuilder
    {
        $this->parameterMapping->phpType = $phpType;
        return $this;
    }

    public function dbalType(?DbalType $dbalType): ParameterMappingBuilder
    {
        $this->parameterMapping->dbalType = $dbalType;
        return $this;
    }

    public function numericScale(int $numericScale): ParameterMappingBuilder
    {
        $this->parameterMapping->numericScale = $numericScale;
        return $this;
    }

    public function resultMapId(?string $resultMapId): ParameterMappingBuilder
    {
        $this->parameterMapping->resultMapId = $resultMapId;
        return $this;
    }

    public function typeHandler(?TypeHandlerInterface $typeHandler): ParameterMappingBuilder
    {
        $this->parameterMapping->typeHandler = $typeHandler;
        return $this;
    }

    public function dbalTypeName(string $dbalTypeName): ParameterMappingBuilder
    {
        $this->parameterMapping->dbalTypeName = $dbalTypeName;
        return $this;
    }

    public function expression(string $expression): ParameterMappingBuilder
    {
        $this->parameterMapping->expression = $expression;
        return $this;
    }

    public function build(): ParameterMapping
    {
        $this->resolveTypeHandler();
        $this->validate();
        return $this->parameterMapping;
    }

    private function validate(): void
    {
        if (Result::class == $this->parameterMapping->phpType) {
            if ($this->parameterMapping->resultMapId === null) {
                throw new IllegalStateException("Missing resultmap in property");
            }
        } else {
            if ($this->parameterMapping->typeHandler == null) {
                throw new IllegalStateException("Type handler was null on parameter mapping for property");
            }
        }
    }

    private function resolveTypeHandler(): void
    {
        if ($this->parameterMapping->typeHandler == null && $this->parameterMapping->phpType != null) {
            $configuration = $this->parameterMapping->configuration;
            $typeHandlerRegistry = $configuration->getTypeHandlerRegistry();
            $this->parameterMapping->typeHandler = $typeHandlerRegistry->getTypeHandler($this->parameterMapping->phpType, $this->parameterMapping->dbalType);
        }
    }
}
