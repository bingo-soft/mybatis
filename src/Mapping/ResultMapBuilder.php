<?php

namespace MyBatis\Mapping;

use MyBatis\Annotations\Param;
use MyBatis\Builder\BuilderException;
use MyBatis\Session\Configuration;

class ResultMapBuilder
{
    private $resultMap;

    public function __construct(Configuration $configuration, string $id, ?string $type, array $resultMappings, ?bool $autoMapping)
    {
        $this->resultMap = new ResultMap();
        $this->resultMap->configuration = $configuration;
        $this->resultMap->id = $id;
        $this->resultMap->type = $type;
        $this->resultMap->resultMappings = $resultMappings;
        $this->resultMap->autoMapping = $autoMapping;
    }

    public function discriminator(?Discriminator $discriminator): ResultMapBuilder
    {
        $this->resultMap->discriminator = $discriminator;
        return $this;
    }

    public function type(): string
    {
        return $this->resultMap->type;
    }

    public function build(): ResultMap
    {
        if ($this->resultMap->id == null) {
            throw new \Exception("ResultMaps must have an id");
        }
        $this->resultMap->mappedColumns = [];
        $this->resultMap->mappedProperties = [];
        $this->resultMap->idResultMappings = [];
        $this->resultMap->constructorResultMappings = [];
        $this->resultMap->propertyResultMappings = [];
        $constructorArgNames = [];
        foreach ($this->resultMap->resultMappings as $resultMapping) {
            $this->resultMap->hasNestedQueries = $this->resultMap->hasNestedQueries || $resultMapping->getNestedQueryId() !== null;
            $this->resultMap->hasNestedResultMaps = $this->resultMap->hasNestedResultMaps || ($resultMapping->getNestedResultMapId() !== null && $resultMapping->getResultSet() === null);
            $column = $resultMapping->getColumn();
            if ($column !== null) {
                $this->resultMap->mappedColumns[] = strtoupper($column);
            } elseif ($resultMapping->isCompositeResult()) {
                foreach ($resultMapping->getComposites() as $compositeResultMapping) {
                    $compositeColumn = $compositeResultMapping->getColumn();
                    if ($compositeColumn !== null) {
                        $this->resultMap->mappedColumns[] = strtoupper($compositeColumn);
                    }
                }
            }
            $property = $resultMapping->getProperty();
            if ($property !== null) {
                $this->resultMap->mappedProperties[] = $property;
            }
            if (in_array(ResultFlag::CONSTRUCTOR, $resultMapping->getFlags())) {
                $this->resultMap->constructorResultMappings[] = $resultMapping;
                if ($resultMapping->getProperty() !== null) {
                    $constructorArgNames[] = $resultMapping->getProperty();
                }
            } else {
                $this->resultMap->propertyResultMappings[] = $resultMapping;
            }
            if (in_array(ResultFlag::ID, $resultMapping->getFlags())) {
                $this->resultMap->idResultMappings[] = $resultMapping;
            }
        }
        if (empty($this->resultMap->idResultMappings)) {
            $this->resultMap->idResultMappings = $this->resultMap->resultMappings;
        }
        if (!empty($constructorArgNames)) {
            $actualArgNames = $this->argNamesOfMatchingConstructor($constructorArgNames);
            if ($actualArgNames === null) {
                throw new BuilderException(
                    "Error in result map '" + $this->resultMap->id
                    . "'. Failed to find a constructor"
                );
            }
            usort($this->resultMap->constructorResultMappings, function ($o1, $o2) use ($actualArgNames) {
                $paramIdx1 = array_search($o1->getProperty(), $actualArgNames);
                $paramIdx2 = array_search($o2->getProperty(), $actualArgNames);
                return $paramIdx1 - $paramIdx2;
            });
        }
        // lock down collections
        return $this->resultMap;
    }

    private function argNamesOfMatchingConstructor(array $constructorArgNames): array
    {
        $ref = new \ReflectionClass($this->resultMap->type);
        $constructor = $ref->getConstructor();
        $paramNames = [];
        $attributes = $constructor->getAttributes(Param::class);
        if (!empty($attributes)) {
            for ($i = 0; $i < count($attributes); $i += 1) {
                $paramNames[] = $attributes[$i]->newInstance()->value();
            }
        } else {
            $parameters = $constructor->getParameters();
            foreach ($parameters as $parameter) {
                $paramNames[] = $parameter->name;
            }
        }
        return $paramNames;
    }
}
