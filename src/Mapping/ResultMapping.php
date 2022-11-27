<?php

namespace MyBatis\Mapping;

use MyBatis\Session\Configuration;
use MyBatis\Type\{
    DbalType,
    TypeHandlerInterface,
    TypeHandlerRegistry
};

class ResultMapping
{
    public $configuration;
    public $property;
    public $column;
    public $phpType;
    public $dbalType;
    public $typeHandler;
    public $nestedResultMapId;
    public $nestedQueryId;
    public $notNullColumns = [];
    public $columnPrefix;
    public $flags = [];
    public $composites = [];
    public $resultSet;
    public $foreignColumn;
    public $lazy = false;

    public function __construct()
    {
    }

    public function getProperty(): ?string
    {
        return $this->property;
    }

    public function getColumn(): ?string
    {
        return $this->column;
    }

    public function getPhpType(): string
    {
        return $this->phpType;
    }

    public function getDbalType(): DbalType
    {
        return $this->dbalType;
    }

    public function getTypeHandler(): TypeHandlerInterface
    {
        return $this->typeHandler;
    }

    public function getNestedResultMapId(): ?string
    {
        return $this->nestedResultMapId;
    }

    public function getNestedQueryId(): ?string
    {
        return $this->nestedQueryId;
    }

    public function getNotNullColumns(): array
    {
        return $this->notNullColumns;
    }

    public function getColumnPrefix(): ?string
    {
        return $this->columnPrefix;
    }

    public function getFlags(): array
    {
        return $this->flags;
    }

    public function getComposites(): array
    {
        return $this->composites;
    }

    public function isCompositeResult(): bool
    {
        return !empty($this->composites);
    }

    public function getResultSet(): ?string
    {
        return $this->resultSet;
    }

    public function getForeignColumn(): string
    {
        return $this->foreignColumn;
    }

    public function setForeignColumn(string $foreignColumn): void
    {
        $this->foreignColumn = $foreignColumn;
    }

    public function isLazy(): bool
    {
        return $this->lazy;
    }

    public function setLazy(bool $lazy): void
    {
        $this->lazy = $lazy;
    }

    public function isSimple(): bool
    {
        return $this->nestedResultMapId === null && $this->nestedQueryId === null && $this->resultSet === null;
    }

    public function equals($o = null): bool
    {
        if ($this == $o) {
            return true;
        }
        if ($o == null || get_class($this) != get_class($o)) {
            return false;
        }

        return $this->property != null && $this->property == $o->property;
    }

    public function __toString()
    {
        $sb = "ResultMapping{";
        //$sb .= "configuration=" . configuration); // configuration doesn't have a useful .toString()
        $sb .= "property='" . $this->property . '\'';
        $sb .= ", column='" . $this->column . '\'';
        $sb .= ", phpType=" . $this->phpType;
        $sb .= ", dbalType=" . $this->dbalType;
        //$sb .= ", typeHandler=" . typeHandler); // typeHandler also doesn't have a useful .toString()
        $sb .= ", nestedResultMapId='" . $this->nestedResultMapId . '\'';
        $sb .= ", nestedQueryId='" . $this->nestedQueryId . '\'';
        $sb .= ", notNullColumns=" . json_encode($this->notNullColumns);
        $sb .= ", columnPrefix='" . $this->columnPrefix . '\'';
        $sb .= ", flags=" . json_encode($this->flags);
        $sb .= ", composites=" . json_encode($this->composites);
        $sb .= ", resultSet='" . $this->resultSet . '\'';
        $sb .= ", foreignColumn='" . $this->foreignColumn . '\'';
        $sb .= ", lazy=" . $this->lazy;
        $sb .= '}';
        return $sb;
    }
}
