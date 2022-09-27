<?php

namespace MyBatis\Executor\Loader;

use MyBatis\Cache\CacheKey;
use MyBatis\Executor\{
    ExecutorInterface,
    ExecutorException,
    ResultExtractor
};
use MyBatis\Mapping\{
    BoundSql,
    Environment,
    MappedStatement
};
use MyBatis\Session\{
    Configuration,
    ExecutorType,
    RowBounds
};
use MyBatis\Transaction\{
    TransactionFactoryInterface,
    TransactionInterface
};

class ResultLoader
{
    public $configuration;
    protected $executor;
    public $mappedStatement;
    public $parameterObject;
    public $targetType;
    public $cacheKey;
    public $boundSql;
    protected $resultExtractor;

    protected $loaded;
    protected $resultObject;

    public function __construct(Configuration $config, ExecutorInterface $executor, MappedStatement $mappedStatement, $parameterObject, $targetType, CacheKey $cacheKey, BoundSql $boundSql)
    {
        $this->configuration = $config;
        $this->executor = $executor;
        $this->mappedStatement = $mappedStatement;
        $this->parameterObject = $parameterObject;
        $this->targetType = $targetType;
        $this->cacheKey = $cacheKey;
        $this->boundSql = $boundSql;
        $this->resultExtractor = new ResultExtractor($configuration);
    }

    public function loadResult()
    {
        $list = $this->selectList();
        $this->resultObject = $this->resultExtractor->extractObjectFromList($list, $this->targetType);
        return $this->resultObject;
    }

    private function selectList(): array
    {
        $localExecutor = $this->executor;
        if ($localExecutor->isClosed()) {
            $localExecutor = $this->newExecutor();
        }
        try {
            return $localExecutor->query($this->mappedStatement, $this->parameterObject, RowBounds::DEFAULT, null, $this->cacheKey, $this->boundSql);
        } finally {
            if ($localExecutor != $this->executor) {
                $localExecutor->close(false);
            }
        }
    }

    private function newExecutor(): ExecutorInterface
    {
        $environment = $this->configuration->getEnvironment();
        if ($environment === null) {
            throw new ExecutorException("ResultLoader could not load lazily.  Environment was not configured.");
        }
        $ds = $environment->getDataSource();
        if ($ds === null) {
            throw new ExecutorException("ResultLoader could not load lazily.  DataSource was not configured.");
        }
        $transactionFactory = $environment->getTransactionFactory();
        $tx = $transactionFactory->newTransaction($ds, null, false);
        return $this->configuration->newExecutor($tx, ExecutorType::SIMPLE);
    }

    public function wasNull(): bool
    {
        return $this->resultObject === null;
    }
}
