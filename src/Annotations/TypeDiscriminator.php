<?php

namespace MyBatis\Annotations;

use MyBatis\Type\{
    DbalType,
    TypeHandlerInterface,
    UnknownTypeHandler
};

#[Attribute(Attribute::TARGET_METHOD)]
class TypeDiscriminator
{
    private $dbalType;

    public function __construct(
        private string $column = '',
        private string $phpType = 'void',
        private string $dbalTypeCode = 'UNDEFINED',
        private string $typeHandler = UnknownTypeHandler::class,
        private array $cases = []
    ) {
        $this->dbalType = DbalType::forCode($dbalTypeCode);
    }

    public function column(): string
    {
        return $this->column;
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

    public function cases(): array
    {
        return $this->cases;
    }
}
