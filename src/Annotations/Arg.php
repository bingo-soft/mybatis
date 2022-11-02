<?php

namespace MyBatis\Annotations;

use MyBatis\Type\{
    DbalType,
    TypeHandlerInterface,
    UnknownTypeHandler
};

#[Attribute(Attribute::TARGET_METHOD)]
#[Attribute(Attribute::IS_REPEATABLE)]
class Arg
{
    public function __construct(
        private bool $id = false,
        private string $column = '',
        private string $name = '',
        private string $phpType = 'void',
        private string $typeHandler = UnknownTypeHandler::class,
        private string $select = '',
        private string $columnPrefix = ''
    ) {
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

    public function typeHandler(): string
    {
        return $this->typeHandler;
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
