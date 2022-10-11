<?php

namespace MyBatis\Executor;

use Doctrine\DBAL\{
    Connection,
    Statement
};
use MyBatis\Cache\CacheKey;
use MyBatis\Cache\Impl\PerpetualCache;
use MyBatis\Cursor\CursorInterface;
use MyBatis\Executor\Statement\StatementUtil;
use MyBatis\Mapping\{
    BoundSql,
    MappedStatement,
    ParameterMapping,
    ParameterMode,
    StatementType
};
use MyBatis\Reflection\MetaObject;
use MyBatis\Session\{
    Configuration,
    LocalCacheScope,
    ResultHandlerInterface,
    RowBounds
};
use MyBatis\Transaction\TransactionInterface;
use MyBatis\Type\TypeHandlerRegistry;

abstract class BaseExecutor implements ExecutorInterface
{
    protected $transaction;
    protected $wrapper;
  
    protected $deferredLoads;
    protected $localCache;
    //protected $localOutputParameterCache;
    protected $configuration;
  
    protected $queryStack;
    private $closed;
  
    public function __construct(Configuration $configuration, TransactionInterface $transaction)
    {
        $this->transaction = $transaction;
        $this->deferredLoads = [];
        $this->localCache = new PerpetualCache("LocalCache");
        //$this->localOutputParameterCache = new PerpetualCache("LocalOutputParameterCache");
        $this->closed = false;
        $this->configuration = $configuration;
        $this->wrapper = $this;
    }

    public function getTransaction(): TransactionInterface
    {
        if ($this->closed) {
            throw new ExecutorException("Executor was closed.");
        }
        return $this->transaction;
    }
  
    public function close(bool $forceRollback): void
    {
        try {
            try {
                $this->rollback($forceRollback);
            } finally {
                if ($this->transaction !== null) {
                    $this->transaction->close();
                }
            }
        } catch (\Exception $e) {
            // Ignore. There's nothing that can be done at this point.
            //log.warn("Unexpected exception on closing transaction.  Cause: " + e);
        } finally {
            $this->transaction = null;
            $this->deferredLoads = [];
            $this->localCache = null;
            //$this->localOutputParameterCache = null;
            $this->closed = true;
        }
    }

    public function isClosed(): bool
    {
        return $this->closed;
    }
  
    public function update(MappedStatement $ms, $parameter): int
    {
        if ($this->closed) {
            throw new ExecutorException("Executor was closed.");
        }
        $this->clearLocalCache();
        return $this->doUpdate($ms, $parameter);
    }
  
    public function flushStatements(bool $isRollBack = false): array
    {
        if ($this->closed) {
            throw new ExecutorException("Executor was closed.");
        }
        return $this->doFlushStatements($isRollBack);
    }

    public function query(MappedStatement $ms, $parameter, RowBounds $rowBounds, ResultHandlerInterface $resultHandler = null, CacheKey $cacheKey = null, BoundSql $boundSql = null): array
    {
        if ($cacheKey === null) {
            $boundSql = $ms->getBoundSql($parameter);
            $key = $this->createCacheKey($ms, $parameter, $rowBounds, $boundSql);
            return $this->query($ms, $parameter, $rowBounds, $resultHandler, $key, $boundSql);
        } else {
            if ($this->closed) {
                throw new ExecutorException("Executor was closed.");
            }
            if ($this->queryStack == 0 && $ms->isFlushCacheRequired()) {
                $this->clearLocalCache();
            }
            $list = [];
            try {
                $this->queryStack += 1;
                $list = $resultHandler == null ? $this->localCache->getObject($cacheKey) : null;
                if (!empty($list)) {
                    $this->handleLocallyCachedOutputParameters($ms, $cacheKey, $parameter, $boundSql);
                } else {
                    $list = $this->queryFromDatabase($ms, $parameter, $rowBounds, $resultHandler, $cacheKey, $boundSql);
                }
            } finally {
                $this->queryStack -= 1;
            }
            if ($this->queryStack == 0) {
                foreach ($this->deferredLoads as $deferredLoad) {
                    $deferredLoad->load();
                }
                // issue #601
                $this->deferredLoads = [];
                if ($this->configuration->getLocalCacheScope() == LocalCacheScope::STATEMENT) {
                    // issue #482
                    $this->clearLocalCache();
                }
            }
            return $list;
        }
    }
  
    public function queryCursor(MappedStatement $ms, $parameter, RowBounds $rowBounds): CursorInterface
    {
        $boundSql = $ms->getBoundSql($parameter);
        return $this->doQueryCursor($ms, $parameter, $rowBounds, $boundSql);
    }
  
    public function deferLoad(MappedStatement $ms, MetaObject $resultObject, string $property, CacheKey $key, string $targetType): void
    {
        if ($this->closed) {
            throw new ExecutorException("Executor was closed.");
        }
        $deferredLoad = new DeferredLoad($resultObject, $property, $key, $this->localCache, $this->configuration, $targetType);
        if ($deferredLoad->canLoad()) {
            $deferredLoad->load();
        } else {
            $this->deferredLoads[] = new DeferredLoad($resultObject, $property, $key, $this->localCache, $this->configuration, $targetType);
        }
    }
  
    public function createCacheKey(MappedStatement $ms, $parameterObject, RowBounds $rowBounds, BoundSql $boundSql): CacheKey
    {
        if ($this->closed) {
            throw new ExecutorException("Executor was closed.");
        }
        $cacheKey = new CacheKey();
        $cacheKey->update($ms->getId());
        $cacheKey->update($rowBounds->getOffset());
        $cacheKey->update($rowBounds->getLimit());
        $cacheKey->update($boundSql->getSql());
        $parameterMappings = $boundSql->getParameterMappings();
        $typeHandlerRegistry = $ms->getConfiguration()->getTypeHandlerRegistry();
        // mimic DefaultParameterHandler logic
        foreach ($parameterMappings as $parameterMapping) {
            if ($parameterMapping->getMode() != ParameterMode::OUT) {
                $value = null;
                $propertyName = $parameterMapping->getProperty();
                if ($boundSql->hasAdditionalParameter($propertyName)) {
                    $value = $boundSql->getAdditionalParameter($propertyName);
                } elseif ($parameterObject === null) {
                    $value = null;
                } elseif ($typeHandlerRegistry->hasTypeHandler(get_class($parameterObject))) {
                    $value = $parameterObject;
                } else {
                    $metaObject = $this->configuration->newMetaObject($parameterObject);
                    $value = $metaObject->getValue($propertyName);
                }
                $cacheKey->update($value);
            }
        }
        if ($this->configuration->getEnvironment() !== null) {
            // issue #176
            $cacheKey->update($this->configuration->getEnvironment()->getId());
        }
        return $cacheKey;
    }
  
    public function isCached(MappedStatement $ms, CacheKey $key): bool
    {
        return $this->localCache->getObject($key) !== null;
    }
  
    public function commit(bool $required): void
    {
        if ($this->closed) {
            throw new ExecutorException("Cannot commit, transaction is already closed");
        }
        $this->clearLocalCache();
        $this->flushStatements();
        if ($required) {
           $this->transaction->commit();
        }
    }
  
    public function rollback(bool $required): void
    {
        if (!$this->closed) {
            try {
                $this->clearLocalCache();
                $this->flushStatements(true);
            } finally {
                if ($required) {
                    $this->transaction->rollback();
                }
            }
        }
    }
  
    public function clearLocalCache(): void
    {
        if (!$this->closed) {
            $this->localCache->clear();
            //localOutputParameterCache.clear();
        }
    }
  
    abstract protected function doUpdate(MappedStatement $ms, $parameter): int;
  
    abstract protected function doFlushStatements(bool $isRollback): array;
  
    abstract protected function doQuery(MappedStatement $ms, $parameter, RowBounds $rowBounds, ResultHandlerInterface $resultHandler, BoundSql $boundSql): array;
  
    abstract protected function doQueryCursor(MappedStatement $ms, $parameter, RowBounds $rowBounds, BoundSql $boundSql): CursorInterface;
  
    protected function closeStatement(Statement $statement): void
    {
        if ($statement !== null) {
            try {
                $wrappedStatement = $statement->getWrappedStatement();
                if (method_exists($wrappedStatement, 'closeCursor')) {
                    $wrappedStatement->closeCursor();
                }
            } catch (\Exception $e) {
                // ignore
            }
        }
    }
  
    /**
     * Apply a transaction timeout.
     */
    protected function applyTransactionTimeout(Statement $statement): void
    {
        //StatementUtil.applyTransactionTimeout(statement, statement.getQueryTimeout(), transaction.getTimeout());
    }
  
    private function handleLocallyCachedOutputParameters(MappedStatement $ms, CacheKey $key, $parameter, BoundSql $boundSql): void
    {
        //
    }
  
    private function queryFromDatabase(MappedStatement $ms, $parameter, RowBounds $rowBounds, ResultHandlerInterface $resultHandler, CacheKey $key, BoundSql $boundSql): array
    {
        $list = [];
        $this->localCache->putObject($key, ExecutionPlaceholder::EXECUTION_PLACEHOLDER);
        try {
            $list = $this->doQuery($ms, $parameter, $rowBounds, $resultHandler, $boundSql);
        } finally {
            $this->localCache->removeObject($key);
        }
        $this->localCache->putObject($key, $list);
        return $list;
    }
  
    public function getConnection(/*Log statementLog*/): Connection
    {
        return $this->transaction->getConnection();
    }
  
    public function setExecutorWrapper(ExecutorInterface $wrapper): void
    {
        $this->wrapper = $wrapper;
    }
}
