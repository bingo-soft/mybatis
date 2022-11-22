<?php

namespace MyBatis\Executor\Statement;

use Doctrine\DBAL\{
    Connection,
    Result,
    Statement
};
use MyBatis\Cursor\CursorInterface;
use MyBatis\Executor\ExecutorInterface;
use MyBatis\Executor\Keygen\{
    DbalKeyGenerator,
    KeyGeneratorInterface
};
use MyBatis\Mapping\{
    BoundSql,
    MappedStatement,
    ResultSetType
};
use MyBatis\Session\{
    ResultHandlerInterface,
    RowBounds
};

class PreparedStatementHandler extends BaseStatementHandler
{
    public function __construct(ExecutorInterface $executor, MappedStatement $mappedStatement, $parameter, ?RowBounds $rowBounds, ?ResultHandlerInterface $resultHandler, ?BoundSql $boundSql)
    {
        parent::__construct($executor, $mappedStatement, $parameter, $rowBounds, $resultHandler, $boundSql);
    }

    public function update(Statement $statement): int
    {
        $rows = $statement->executeStatement();
        $parameterObject = $this->boundSql->getParameterObject();
        $keyGenerator = $this->mappedStatement->getKeyGenerator();
        $keyGenerator->processAfter($this->executor, $this->mappedStatement, $statement, $parameterObject);
        return $rows;
    }

    public function query(Statement $statement, ?ResultHandlerInterface $resultHandler): array
    {
        $statement->executeStatement();
        return $this->resultSetHandler->handleResultSets($statement);
    }

    public function queryCursor(Statement $statement): CursorInterface
    {
        $statement->executeStatement();
        return $this->resultSetHandler->handleCursorResultSets($statement);
    }

    public function instantiateStatement(Connection $connection): Statement
    {
        $sql = $this->boundSql->getSql();
        return $connection->prepare($sql);
    }

    public function parameterize(Statement $statement): void
    {
        $this->parameterHandler->setParameters($statement);
    }
}
