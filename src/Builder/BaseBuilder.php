<?php

namespace MyBatis\Builder;

use MyBatis\Mapping\{
    ParameterMode,
    ResultSetType
};
use MyBatis\Parsing\Boolean;
use MyBatis\Session\Configuration;
use MyBatis\Type\{
    DbalType,
    TypeAliasRegistry,
    TypeHandlerInterface,
    TypeHandlerRegistry
};

abstract class BaseBuilder
{
    protected $configuration;
    protected $typeAliasRegistry;
    protected $typeHandlerRegistry;

    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
        $this->typeAliasRegistry = $this->configuration->getTypeAliasRegistry();
        $this->typeHandlerRegistry = $this->configuration->getTypeHandlerRegistry();
    }

    public function getConfiguration(): Configuration
    {
        return $this->configuration;
    }

    protected function parseExpression(?string $regex, string $defaultValue): string
    {
        return $regex === null ? $defaultValue : $regex;
    }

    protected function booleanValueOf(?string $value, bool $defaultValue): bool
    {
        return $value === null ? $defaultValue : Boolean::parseBoolean($value);
    }

    protected function integerValueOf(?string $value, int $defaultValue): int
    {
        return $value === null ? $defaultValue : intval($value);
    }

    protected function stringSetValueOf(string $value, string $defaultValue): array
    {
        $value = $value === null ? $defaultValue : $value;
        return explode(",", $value);
    }

    protected function resolveDbalType(?string $alias): ?DbalType
    {
        if ($alias === null) {
            return null;
        }
        try {
            return DbalType::forCode($alias);
        } catch (\Exception $e) {
            throw new BuilderException("Error resolving DbalType. Cause: " . $e->getMessage());
        }
    }

    protected function resolveResultSetType(?string $alias): ?ResultSetType
    {
        if ($alias === null) {
            return null;
        }
        try {
            //@CHECK IT!
            return ResultSetType::valueOf($alias);
        } catch (\Exception $e) {
            throw new BuilderException("Error resolving ResultSetType. Cause: " . $e->getMessage());
        }
    }

    protected function resolveParameterMode(?string $alias): string
    {
        return strtolower($alias);
    }

    protected function createInstance(?string $alias)
    {
        $clazz = $this->resolveClass($alias);
        if ($clazz === null) {
            return null;
        }
        try {
            return new $clazz();
        } catch (\Exception $e) {
            throw new BuilderException("Error creating instance. Cause: " . $e->getMessage());
        }
    }

    protected function resolveClass(?string $alias)
    {
        if ($alias === null) {
            return null;
        }
        try {
            return $this->resolveAlias($alias);
        } catch (\Exception $e) {
            throw new BuilderException("Error resolving class. Cause: " . $e->getMessage());
        }
    }

    protected function resolveTypeHandler(string $phpType, ?string $typeHandlerAliasOrType): ?TypeHandlerInterface
    {
        if ($typeHandlerAliasOrType === null) {
            return null;
        }
        if (class_exists($typeHandlerAliasOrType)) {
            $handler = $this->typeHandlerRegistry->getMappingTypeHandler($typeHandlerAliasOrType);
            if ($handler === null) {
                // not in registry, create a new one
                $handler = $typeHandlerRegistry->getInstance($phpType, $typeHandlerAliasOrType);
            }
            return $handler;
        } else {
            $type = $this->resolveClass($typeHandlerAliasOrType);
            if ($type !== null && !is_a($type, TypeHandlerInterface::class, true)) {
                throw new BuilderException("Type " . $type . " is not a valid TypeHandler because it does not implement TypeHandler interface");
            }
            return $this->resolveTypeHandler($phpType, $type);
        }
    }

    protected function resolveAlias(string $alias)
    {
        return $this->typeAliasRegistry->resolveAlias($alias);
    }
}
