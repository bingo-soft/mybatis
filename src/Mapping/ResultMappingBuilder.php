<?php

namespace MyBatis\Mapping;

use MyBatis\Session\Configuration;
use MyBatis\Type\{
    DbalType,
    TypeHandlerInterface,
    TypeHandlerRegistry
};

class ResultMappingBuilder
{
    private $resultMapping;

    public function __construct(Configuration $configuration, string $property, ?string $column = null, /*TypeHandlerInterface|string*/$handlerOrType)
    {
        $this->resultMapping = new ResultMapping();
        $this->resultMapping->configuration = $configuration;
        $this->resultMapping->property = $property;
        $this->resultMapping->flags = [];
        $this->resultMapping->composites = [];
        $this->resultMapping->lazy = $configuration->isLazyLoadingEnabled();
        $this->resultMapping->column = $column;
        if ($handlerOrType instanceof TypeHandlerInterface) {
            $this->resultMapping->typeHandler = $handlerOrType;
        } elseif (is_string($handlerOrType)) {
            $this->phpType = $handlerOrType;
        }
    }

    public function phpType(string $phpType): ResultMappingBuilder
    {
        $this->resultMapping->phpType = $phpType;
        return $this;
    }

    public function dbalType(DbalType $dbalType): ResultMappingBuilder
    {
        $this->resultMapping->dbalType = $dbalType;
        return $this;
    }

    public function nestedResultMapId(string $nestedResultMapId): ResultMappingBuilder
    {
        $this->resultMapping->nestedResultMapId = $nestedResultMapId;
        return $this;
    }

    public function nestedQueryId(string $nestedQueryId): ResultMappingBuilder
    {
        $this->resultMapping->nestedQueryId = $nestedQueryId;
        return $this;
    }

    public function resultSet(string $resultSet): ResultMappingBuilder
    {
        $this->resultMapping->resultSet = $resultSet;
        return $this;
    }

    public function foreignColumn(string $foreignColumn): ResultMappingBuilder
    {
        $this->resultMapping->foreignColumn = $foreignColumn;
        return $this;
    }

    public function notNullColumns(array $notNullColumns): ResultMappingBuilder
    {
        $this->resultMapping->notNullColumns = $notNullColumns;
        return $this;
    }

    public function columnPrefix(string $columnPrefix): ResultMappingBuilder
    {
        $this->resultMapping->columnPrefix = $columnPrefix;
        return $this;
    }

    public function flags(array $flags): ResultMappingBuilder
    {
        $this->resultMapping->flags = $flags;
        return $this;
    }

    public function typeHandler(TypeHandlerInterface $typeHandler): ResultMappingBuilder
    {
        $this->resultMapping->typeHandler = $typeHandler;
        return $this;
    }

    public function composites(array $composites): ResultMappingBuilder
    {
        $this->resultMapping->composites = $composites;
        return $this;
    }

    public function lazy(bool $lazy): ResultMappingBuilder
    {
        $this->resultMapping->lazy = $lazy;
        return $this;
    }

    public function build(): ResultMapping
    {
        // lock down collections
        $this->resolveTypeHandler();
        $this->validate();
        return $this->resultMapping;
    }

    private function validate(): void
    {
        // Issue #697: cannot define both nestedQueryId and nestedResultMapId
        if ($this->resultMapping->nestedQueryId !== null && $this->resultMapping->nestedResultMapId !== null) {
            throw new \Exception("Cannot define both nestedQueryId and nestedResultMapId in property " . $this->resultMapping->property);
        }
        // Issue #5: there should be no mappings without typehandler
        if ($this->resultMapping->nestedQueryId === null && $this->resultMapping->nestedResultMapId === null && $this->resultMapping->typeHandler === null) {
            throw new \Exception("No typehandler found for property " . $this->resultMapping->property);
        }
        // Issue #4 and GH #39: column is optional only in nested resultmaps but not in the rest
        if ($this->resultMapping->nestedResultMapId === null && $this->resultMapping->column === null && empty($this->resultMapping->composites)) {
            throw new \Exception("Mapping is missing column attribute for property " . $this->resultMapping->property);
        }
        if ($this->resultMapping->getResultSet() !== null) {
            $numColumns = 0;
            if ($this->resultMapping->column !== null) {
                $numColumns = count(explode(',', $this->resultMapping->column));
            }
            $numForeignColumns = 0;
            if ($this->resultMapping->foreignColumn !== null) {
                $numForeignColumns = count(explode(',', $this->resultMapping->foreignColumn));
            }
            if ($numColumns != $numForeignColumns) {
                throw new \Exception("There should be the same number of columns and foreignColumns in property " . $this->resultMapping->property);
            }
        }
    }

    private function resolveTypeHandler(): void
    {
        if ($this->resultMapping->typeHandler === null && $this->resultMapping->phpType !== null) {
            $configuration = $this->resultMapping->configuration;
            $typeHandlerRegistry = $configuration->getTypeHandlerRegistry();
            $this->resultMapping->typeHandler = $typeHandlerRegistry->getTypeHandler($this->resultMapping->phpType, $this->resultMapping->dbalType);
        }
    }

    public function column(string $column): ResultMappingBuilder
    {
        $this->resultMapping->column = $column;
        return $this;
    }
}
