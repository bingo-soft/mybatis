<?php

namespace MyBatis\Annotations;

use Attribute;
use MyBatis\Mapping\{
    ResultSetType,
    StatementType
};

#[Attribute(Attribute::TARGET_METHOD)]
class Options
{
    private $resultSetType;

    public function __construct(
        private bool $useCache = true,
        private string $flushCache = FlushCachePolicy::DEFAULT,
        private int $resultSetCode = ResultSetType::DEFAULT,
        private string $statementType = StatementType::PREPARED,
        private int $fetchSize = 1,
        private int $timeout = -1,
        private bool $useGeneratedKeys = false,
        private string $keyProperty = "",
        private string $keyColumn = "",
        private string $resultSets = "",
        private string $databaseId = ""
    ) {
        $this->resultSetType = ResultSetType::forCode($this->resultSetCode);
    }

    public function useCache(): bool
    {
        return $this->useCache;
    }

    public function flushCache(): string
    {
        return $this->flushCache;
    }

    public function resultSetType(): ResultSetType
    {
        return $this->resultSetType;
    }

    public function statementType(): string
    {
        return $this->statementType;
    }

    public function fetchSize(): int
    {
        return $this->fetchSize;
    }

    public function timeout(): int
    {
        return $this->timeout;
    }

    public function useGeneratedKeys(): bool
    {
        return $this->useGeneratedKeys;
    }

    public function keyProperty(): string
    {
        return $this->keyProperty;
    }

    public function keyColumn(): string
    {
        return $this->keyColumn;
    }

    public function resultSets(): string
    {
        return $this->resultSets;
    }

    public function databaseId(): string
    {
        return $this->databaseId;
    }
}
