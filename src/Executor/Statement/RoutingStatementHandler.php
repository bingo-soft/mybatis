<?php

namespace MyBatis\Executor\Statement;

use Doctrine\DBAL\{
    Connection,
    Statement
};
use MyBatis\Cursor\CursorInterface;
use MyBatis\Executor\{
    ExecutorInterface,
    ExecutorException
};
use MyBatis\Executor\Parameter\ParameterHandlerInterface;
use MyBatis\Mapping\{
    BoundSql,
    MappedStatement,
    StatementType
};
use MyBatis\Session\{
    ResultHandlerInterface,
    RowBounds
};

class RoutingStatementHandler implements StatementHandlerInterface
{
    private $delegate;

    public function __construct(ExecutorInterface $executor, MappedStatement $ms, $parameter, ?RowBounds $rowBounds, ?ResultHandlerInterface $resultHandler, ?BoundSql $boundSql)
    {
        switch ($ms->getStatementType()) {
            case StatementType::STATEMENT:
                $this->delegate = new SimpleStatementHandler($executor, $ms, $parameter, $rowBounds, $resultHandler, $boundSql);
                break;
            case StatementType::PREPARED:
                $this->delegate = new PreparedStatementHandler($executor, $ms, $parameter, $rowBounds, $resultHandler, $boundSql);
                break;
            default:
                throw new ExecutorException("Unknown statement type: " . $ms->getStatementType());
        }
    }

    public function prepare(Connection $connection, int $transactionTimeout): Statement
    {
        return $this->delegate->prepare($connection, $transactionTimeout);
    }

    public function parameterize(Statement $statement): void
    {
        $this->delegate->parameterize($statement);
    }

    public function update(Statement $statement): int
    {
        return $this->delegate->update($statement);
    }

    public function query(Statement $statement, ?ResultHandlerInterface $resultHandler): array
    {
        return $this->delegate->query($statement, $resultHandler);
    }

    public function queryCursor(Statement $statement): CursorInterface
    {
        return $this->delegate->queryCursor($statement);
    }

    public function getBoundSql(): BoundSql
    {
        return $this->delegate->getBoundSql();
    }

    public function getParameterHandler(): ParameterHandlerInterface
    {
        return $this->delegate->getParameterHandler();
    }
}
