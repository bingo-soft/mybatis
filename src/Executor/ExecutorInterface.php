<?php

namespace MyBatis\Executor;

use MyBatis\Cache\CacheKey;
use MyBatis\Cursor\CursorInterface;
use MyBatis\Mapping\{
    BoundSql,
    MappedStatement
};
use MyBatis\Session\{
    ResultHandlerInterface,
    RowBounds
};
use Util\Reflection\MetaObject;
use MyBatis\Transaction\TransactionInterface;

interface ExecutorInterface
{
    public function update(MappedStatement $ms, $parameter): int;

    public function query(MappedStatement $ms, $parameter, RowBounds $rowBounds, ResultHandlerInterface $resultHandler = null, CacheKey $cacheKey = null, BoundSql $boundSql = null): array;

    //public function queryCursor(MappedStatement $ms, $parameter, RowBounds $rowBounds): CursorInterface;

    public function flushStatements(): array;

    public function commit(bool $required): void;

    public function rollback(bool $required): void;

    public function createCacheKey(MappedStatement $ms, $parameterObject, RowBounds $rowBounds, BoundSql $boundSql): CacheKey;

    public function isCached(MappedStatement $ms, CacheKey $key): bool;

    public function clearLocalCache(): void;

    public function deferLoad(MappedStatement $ms, MetaObject $resultObject, string $property, CacheKey $key, string $targetType): void;

    public function getTransaction(): TransactionInterface;

    public function close(bool $forceRollback): void;

    public function isClosed(): bool;

    public function setExecutorWrapper(ExecutorInterface $executor): void;
}
