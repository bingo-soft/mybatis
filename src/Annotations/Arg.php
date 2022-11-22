<?php

namespace MyBatis\Annotations;

use Attribute;
use MyBatis\Type\{
    DbalType,
    TypeHandlerInterface,
    UnknownTypeHandler
};

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Arg
{
    private $dbalType;

    public function __construct(
        private bool $id = false,
        private string $column = '',
        private string $name = '',
        private string $phpType = 'void',
        private string $typeHandler = UnknownTypeHandler::class,
        private string $select = '',
        private string $resultMap = '',
        private string $columnPrefix = '',
        private string $dbalTypeCode = 'UNDEFINED'
    ) {
        $this->dbalType = DbalType::forCode($dbalTypeCode);
    }

    public function id(): bool
    {
        return $this->id;
    }

    public function column(): string
    {
        return $this->column;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function phpType(): string
    {
        return $this->phpType;
    }

    public function dbalType(): DbalType
    {
        return $this->dbalType;
    }

    public function typeHandler(): string
    {
        return $this->typeHandler;
    }

    public function resultMap(): string
    {
        return $this->resultMap;
    }

    public function select(): string
    {
        return $this->select;
    }

    public function columnPrefix(): string
    {
        return $this->columnPrefix;
    }
}
