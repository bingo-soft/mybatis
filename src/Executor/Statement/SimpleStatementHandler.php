<?php

namespace MyBatis\Executor\Statement;

use Doctrine\DBAL\{
    Connection,
    Statement
};
use MyBatis\Cursor\CursorInterface;
use MyBatis\Executor\ExecutorInterface;
use MyBatis\Executor\Keygen\{
    DbalKeyGenerator,
    KeyGeneratorInterface,
    SelectKeyGenerator
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

class SimpleStatementHandler extends BaseStatementHandler
{
    public function __construct(ExecutorInterface $executor, MappedStatement $mappedStatement, $parameter, RowBounds $rowBounds, ResultHandlerInterface $resultHandler, BoundSql $boundSql)
    {
        parent::__construct($executor, $mappedStatement, $parameter, $rowBounds, $resultHandler, $boundSql);
    }

    public function update(Statement $statement): int
    {
        $parameterObject = $this->boundSql->getParameterObject();
        $keyGenerator = $this->mappedStatement->getKeyGenerator();
        $rows = 0;
        if ($keyGenerator instanceof DbalKeyGenerator) {
            $rows = $statement->executeStatement();
            $keyGenerator->processAfter($this->executor, $this->mappedStatement, $statement, $parameterObject);
        } elseif ($keyGenerator instanceof SelectKeyGenerator) {
            $rows = $statement->executeStatement();
            $keyGenerator->processAfter($this->executor, $this->mappedStatement, $statement, $parameterObject);
        } else {            
            $rows = $this->executor->getTransaction()->getConnection()->executeStatement($sql);
        }
        return $rows;
    }
  
    public function query(Statement $statement, ResultHandlerInterface $resultHandler): array
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
        // N/A
    }  
}
