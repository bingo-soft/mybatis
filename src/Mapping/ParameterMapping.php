<?php

namespace MyBatis\Mapping;

use MyBatis\Session\Configuration;
use MyBatis\Type\{
    DbalType,
    TypeHandlerInterface,
    TypeHandlerRegistry
};

class ParameterMapping
{
    public $configuration;
    public $property;
    public $mode;
    public $phpType = "object";
    public $dbalType;
    public $numericScale;
    public $typeHandler;
    public $resultMapId;
    public $dbalTypeName;
    public $expression;

    public function __construct()
    {
    }

    public function getProperty(): ?string
    {
        return $this->property;
    }

    /**
     * Used for handling output of callable statements.
     *
     * @return the mode
     */
    public function getMode(): ?string
    {
        return $this->mode;
    }

    /**
     * Used for handling output of callable statements.
     *
     * @return the php type
     */
    public function getPhpType(): ?string
    {
        return $this->phpType;
    }

    public function getDbalType(): ?DbalType
    {
        return $this->dbalType;
    }

    /**
    * Used for handling output of callable statements.
    *
    * @return the numeric scale
    */
    public function getNumericScale(): ?int
    {
        return $this->numericScale;
    }

    /**
    * Used when setting parameters to the PreparedStatement.
    *
    * @return the type handler
    */
    public function getTypeHandler(): ?TypeHandlerInterface
    {
        return $this->typeHandler;
    }

    /**
     * Used for handling output of callable statements.
     *
     * @return the result map id
     */
    public function getResultMapId(): ?string
    {
        return $this->resultMapId;
    }

    /**
     * Used for handling output of callable statements.
     *
     * @return the dbal type name
     */
    public function getDbalTypeName(): ?string
    {
        return $this->dbalTypeName;
    }

    /**
     * Expression 'Not used'.
     *
     * @return the expression
     */
    public function getExpression(): ?string
    {
        return $this->expression;
    }

    public function __toString()
    {
        $sb = "ParameterMapping{";
        //$sb .= "configuration=" . configuration); // configuration doesn't have a useful .toString()
        $sb .= "property='" . $this->property . '\'';
        $sb .= ", mode=" . $this->mode;
        $sb .= ", phpType=" . $this->phpType;
        $sb .= ", dbalType=" . $this->dbalType;
        $sb .= ", numericScale=" . $this->numericScale;
        //$sb .= ", typeHandler=" . typeHandler); // typeHandler also doesn't have a useful .toString()
        $sb .= ", resultMapId='" . $this->resultMapId . '\'';
        $sb .= ", dbalTypeName='" . $this->dbalTypeName . '\'';
        $sb .= ", expression='" . $this->expression . '\'';
        $sb .= '}';
        return $sb;
    }
}
