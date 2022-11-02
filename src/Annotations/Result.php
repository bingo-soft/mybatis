<?php

namespace MyBatis\Annotations;

use MyBatis\Type\{
    DbalType,
    TypeHandlerInterface,
    UnknownTypeHandler
};

#[Attribute(Attribute::TARGET_METHOD)]
#[Attribute(Attribute::IS_REPEATABLE)]
class Result
{
    private $dbalType;

    public function __construct(
        private bool $id = false,
        private string $column = '',
        private string $property = '',
        private string $phpType = 'void',
        private string $dbalTypeCode = 'UNDEFINED',
        private string $typeHandler = UnknownTypeHandler::class
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

    public function property(): string
    {
        return $this->property;
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
}
