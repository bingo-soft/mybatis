<?php

namespace MyBatis\Annotations;

use MyBatis\Mapping\StatementType;

#[Attribute(Attribute::TARGET_METHOD)]
#[Attribute(Attribute::IS_REPEATABLE)]
class Select
{
    public function __construct(private array $statement, private string $keyProperty, private bool $before, private string $resultType, private string $keyColumn = "", private string $statementType = StatementType::PREPARED, private string $databaseId = "")
    {
    }

    public function statement(): array
    {
        return $this->statement;
    }

    public function keyProperty(): string
    {
        return $this->keyProperty;
    }

    public function before(): bool
    {
        return $this->before;
    }

    public function resultType(): string
    {
        return $this->resultType;
    }

    public function keyColumn(): string
    {
        return $this->keyColumn;
    }

    public function statementType(): string
    {
        return $this->statementType;
    }

    public function databaseId(): string
    {
        return $this->databaseId;
    }
}
