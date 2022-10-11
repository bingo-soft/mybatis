<?php

namespace MyBatis\Executor;

use MyBatis\Cache\{
    CacheInterface,
    CacheKey,
    TransactionalCacheManager
};
use MyBatis\Cursor\CursorInterface;
use MyBatis\Mapping\{
    BoundSql,
    MappedStatement,
    ParameterMapping,
    ParameterMode,
    StatementType
};
use MyBatis\Reflection\MetaObject;
use MyBatis\Session\{
    ResultHandlerInterface,
    RowBounds
};
use MyBatis\Transaction\TransactionInterface;

class CachingExecutor implements ExecutorInterface
{
    private $delegate;
    private $tcm;// = new TransactionalCacheManager();
  
    public function __construct(ExecutorInterface $delegate)
    {
        $this->tcm = new TransactionalCacheManager();
        $this->delegate = $delegate;
        $this->delegate->setExecutorWrapper($this);
    }

    public function getTransaction(): TransactionInterface
    {
        return $this->delegate->getTransaction();
    }
  
    public function close(bool $forceRollback): void
    {
        try {
            // issues #499, #524 and #573
            if ($forceRollback) {
                $this->tcm->rollback();
            } else {
                $this->tcm->commit();
            }
        } finally {
            $this->delegate->close($forceRollback);
        }
    }
  
    public function isClosed(): bool
    {
        return $this->delegate->isClosed();
    }
  
    public function update(MappedStatement $ms, $parameterObject): int
    {
        $this->flushCacheIfRequired($ms);
        return $this->delegate->update($ms, $parameterObject);
    }
  
    public function queryCursor(MappedStatement $ms, $parameter, RowBounds $rowBounds): CursorInterface
    {
        $this->flushCacheIfRequired($ms);
        return $this->delegate->queryCursor($ms, $parameter, $rowBounds);
    }
  
    public function query(MappedStatement $ms, $parameter, RowBounds $rowBounds, ResultHandlerInterface $resultHandler = null, CacheKey $cacheKey = null, BoundSql $boundSql = null): array
    {
        if ($cacheKey === null) {
            $boundSql = $ms->getBoundSql($parameterObject);
            $key = $this->createCacheKey($ms, $parameterObject, $rowBounds, $boundSql);
            return $this->query($ms, $parameterObject, $rowBounds, $resultHandler, $key, $boundSql);
        } else {
            $cache = $ms->getCache();
            if ($cache !== null) {
                $this->flushCacheIfRequired($ms);
                if ($ms->isUseCache() && $resultHandler === null) {
                    //$this->ensureNoOutParams($ms, $boundSql);
                    $list = $this->tcm->getObject($cache, $key);
                    if (empty($list)) {
                        $list = $this->delegate->query($ms, $parameterObject, $rowBounds, $resultHandler, $cacheKey, $boundSql);
                        $this->tcm->putObject($cache, $cacheKey, $list);
                    }
                    return $list;
                }
            }
            return $this->delegate->query($ms, $parameterObject, $rowBounds, $resultHandler, $cacheKey, $boundSql);
        }
    }

    public function flushStatements(): array
    {
        return $this->delegate->flushStatements();
    }
  
    public function commit(bool $required): void
    {
        $this->delegate->commit($required);
        $this->tcm->commit();
    }
  
    public function rollback(bool $required): void
    {
        try {
            $this->delegate->rollback($required);
        } finally {
            if ($required) {
                $this->tcm->rollback();
            }
        }
    }
  
    public function createCacheKey(MappedStatement $ms, $parameterObject, RowBounds $rowBounds, BoundSql $boundSql): CacheKey
    {
        return $this->delegate->createCacheKey($ms, $parameterObject, $rowBounds, $boundSql);
    }

    public function isCached(MappedStatement $ms, CacheKey $key): bool
    {
        return $this->delegate->isCached($ms, $key);
    }

    public function deferLoad(MappedStatement $ms, MetaObject $resultObject, string $property, CacheKey $key, string $targetType): void
    {
        $this->delegate->deferLoad($ms, $resultObject, $property, $key, $targetType);
    }

    public function clearLocalCache(): void
    {
        $this->delegate->clearLocalCache();
    }
  
    private function flushCacheIfRequired(MappedStatement $ms): void
    {
        $cache = $ms->getCache();
        if ($cache !== null && $ms->isFlushCacheRequired()) {
            $this->tcm->clear($cache);
        }
    }

    public function setExecutorWrapper(ExecutorInterface $executor): void
    {
        throw new UnsupportedOperationException("This method should not be called");
    }  
}
