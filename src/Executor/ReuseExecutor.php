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

class ReuseExecutor extends BaseExecutor
{
    private $statementMap = [];
  
    public function __construct(Configuration $configuration, TransactionInterface $transaction)
    {
        parent::__construct($configuration, $transaction);
    }

    public function doUpdate(MappedStatement $ms, $parameter): int
    {
        $configuration = $ms->getConfiguration();
        $handler = $configuration->newStatementHandler($this, $ms, $parameter, RowBounds::default(), null, null);
        $stmt = $this->prepareStatement($handler/*, $ms->getStatementLog()*/);
        return $handler->update($stmt);
    }
  
    public function doQuery(MappedStatement $ms, $parameter, RowBounds $rowBounds, ResultHandlerInterface $resultHandler, BoundSql $boundSql): array
    {
        $configuration = $ms->getConfiguration();
        $handler = $configuration->newStatementHandler($wrapper, $ms, $parameter, $rowBounds, $resultHandler, $boundSql);
        $stmt = $this->prepareStatement($handler/*, $ms->getStatementLog()*/);
        return $handler->query($stmt, $resultHandler);
    }
  
    protected function doQueryCursor(MappedStatement $ms, $parameter, RowBounds $rowBounds, BoundSql $boundSql): CursorInterface
    {
        $configuration = $ms->getConfiguration();
        $handler = $configuration->newStatementHandler($wrapper, $ms, $parameter, $rowBounds, null, $boundSql);
        $stmt = $this->prepareStatement($handler/*, $ms->getStatementLog()*/);
        return $handler->queryCursor($stmt);
    }
  
    public function doFlushStatements(bool $isRollback): array
    {
        foreach (array_values($this->statementMap) as $stmt) {
            $this->closeStatement($stmt);
        }
        $this->statementMap = [];
        return [];
    }
  
    private function prepareStatement(StatementHandler $handler/*, Log statementLog*/): Statement
    {
        $stmt = null;
        $boundSql = $handler->getBoundSql();
        $sql = $boundSql->getSql();
        if ($this->hasStatementFor($sql)) {
            $stmt = $this->getStatement($sql);
            //applyTransactionTimeout(stmt);
        } else {
            $connection = $this->getConnection(/*statementLog*/);
            $stmt = $handler->prepare($connection, 0/*transaction.getTimeout()*/);
            $this->putStatement($sql, $stmt);
        }
        $handler->parameterize($stmt);
        return $stmt;
    }
  
    private function hasStatementFor(string $sql): bool
    {
        try {
            $statement = null;
            if (array_key_exists($s, $this->statementMap)) {
                $statement = $this->statementMap[$s];
            }
            return $statement !== null/* && !statement.getConnection().isClosed()*/;
        } catch (\Throwable $e) {
            return false;
        }
    }
  
    private function getStatement(string $s): ?Statement
    {
        if (array_key_exists($s, $this->statementMap)) {
            return $this->statementMap[$s];
        }
        return null;
    }
  
    private function putStatement(string $sql, Statement $stmt): void
    {
        $this->statementMap[$sql] = $stmt;
    }  
}
