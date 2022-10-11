<?php

namespace MyBatis\Executor;

use Doctrine\DBAL\{
    Connection,
    Statement
};
use MyBatis\Cursor\CursorInterface;
use MyBatis\Executor\Statement\StatementHandlerInterface;
use MyBatis\Mapping\{
    BoundSql,
    MappedStatement
};
use MyBatis\Session\{
    Configuration,
    ResultHandlerInterface,
    RowBounds
};
use MyBatis\Transaction\TransactionInterface;

class SimpleExecutor extends BaseExecutor
{
    public function __construct(Configuration $configuration, TransactionInterface $transaction)
    {
        parent::__construct($configuration, $transaction);
    }

    public function doUpdate(MappedStatement $ms, $parameter): int
    {
        $stmt = null;
        try {
            $configuration = $ms->getConfiguration();
            $handler = $configuration->newStatementHandler($this, $ms, $parameter, RowBounds::default(), null, null);
            $stmt = $this->prepareStatement($handler, $ms->getStatementLog());
            return $handler->update($stmt);
        } finally {
            $this->closeStatement($stmt);
        }
    }
  
    public function doQuery(MappedStatement $ms, $parameter, RowBounds $rowBounds, ResultHandlerInterface $resultHandler, BoundSql $boundSql): array
    {
        $stmt = null;
        try {
            $configuration = $ms->getConfiguration();
            $handler = $configuration->newStatementHandler($wrapper, $ms, $parameter, $rowBounds, $resultHandler, $boundSql);
            $stmt = $this->prepareStatement($handler, $ms->getStatementLog());
            return $handler->query($stmt, $resultHandler);
        } finally {
            $this->closeStatement($stmt);
        }
    }

    protected function doQueryCursor(MappedStatement $ms, $parameter, RowBounds $rowBounds, BoundSql $boundSql): CursorInterface
    {
        $configuration = $ms->getConfiguration();
        $handler = $configuration->newStatementHandler($wrapper, $ms, $parameter, $rowBounds, null, $boundSql);
        $stmt = $this->prepareStatement($handler, $ms->getStatementLog());
        $cursor = $handler->queryCursor($stmt);
        $stmt->closeOnCompletion();
        return $cursor;
    }

    public function doFlushStatements(bool $isRollback): array
    {
        return [];
    }
  
    private function prepareStatement(StatementHandlerInterface $handler/*, Log statementLog*/): Statement
    {
        $connection = $this->getConnection(/*statementLog*/);
        $stmt = $handler->prepare($connection, 0/*transaction.getTimeout()*/);
        $handler->parameterize($stmt);
        return $stmt;
    }  
}
