<?php

namespace MyBatis\Executor\Loader;

use MyBatis\Executor\BaseExecutor;
use MyBatis\Mapping\{
    BoundSql,
    MappedStatement
};
use MyBatis\Session\{
    ResultHandlerInterface,
    RowBounds
};

class ClosedExecutor extends BaseExecutor
{
    public function __construct()
    {
        parent::__construct(null, null);
    }

    public function isClosed(): bool
    {
        return true;
    }

    protected function doUpdate(MappedStatement $ms, $parameter): int
    {
        throw new \Exception("Not supported.");
    }

    protected function doFlushStatements(bool $isRollback): array
    {
        throw new \Exception("Not supported.");
    }

    protected function doQuery(MappedStatement $ms, $parameter, RowBounds $rowBounds, ResultHandlerInterface $resultHandler, BoundSql $boundSql): array
    {
        throw new \Exception("Not supported.");
    }
}
