<?php

namespace MyBatis\Executor\Statement;

use Doctrine\DBAL\{
    Connection,
    Statement
};
use MyBatis\Executor\{
    ExecutorInterface,
    ExecutorException
};
use MyBatis\Executor\Keygen\KeyGeneratorInterface;
use MyBatis\Executor\Parameter\ParameterHandlerInterface;
use MyBatis\Executor\ResultSet\ResultSetHandlerInterface;
use MyBatis\Mapping\{
    BoundSql,
    MappedStatement
};
use MyBatis\Session\{
    Configuration,
    ResultHandlerInterface,
    RowBounds
};
use MyBatis\Type\TypeHandlerRegistry;

abstract class BaseStatementHandler implements StatementHandlerInterface
{
    protected $configuration;
    protected $typeHandlerRegistry;
    protected $resultSetHandler;
    protected $parameterHandler;

    protected $executor;
    protected $mappedStatement;
    protected $rowBounds;

    protected $boundSql;

    public function __construct(ExecutorInterface $executor, MappedStatement $mappedStatement, $parameterObject, ?RowBounds $rowBounds, ?ResultHandlerInterface $resultHandler, ?BoundSql $boundSql) {
        $this->configuration = $mappedStatement->getConfiguration();
        $this->executor = $executor;
        $this->mappedStatement = $mappedStatement;
        $this->rowBounds = $rowBounds;
        $this->typeHandlerRegistry = $this->configuration->getTypeHandlerRegistry();

        if ($boundSql == null) { // issue #435, get the key before calculating the statement
            $this->generateKeys($parameterObject);
            $boundSql = $this->mappedStatement->getBoundSql($parameterObject);
        }

        $this->boundSql = $boundSql;
        $this->parameterHandler = $this->configuration->newParameterHandler($this->mappedStatement, $parameterObject, $this->boundSql);
        $this->resultSetHandler = $this->configuration->newResultSetHandler($this->executor, $this->mappedStatement, $this->rowBounds, $this->parameterHandler, $resultHandler, $this->boundSql);
    }

    public function getBoundSql(): BoundSql
    {
        return $this->boundSql;
    }

    public function getParameterHandler(): ParameterHandlerInterface
    {
        return $this->parameterHandler;
    }

    public function prepare(Connection $connection, int $transactionTimeout): Statement
    {
        $statement = null;
        try {
            $statement = $this->instantiateStatement($connection);
            //$this->setStatementTimeout($statement, $transactionTimeout);
            //$this->setFetchSize($statement);
            return $statement;
        } catch (\Throwable $e) {
            $this->closeStatement($statement);
            throw new ExecutorException("Error preparing statement.  Cause: " . $e->getMessage());
        }
    }

    abstract public function instantiateStatement(Connection $connection): Statement;

    protected function closeStatement(?Statement $statement): void
    {
        try {
            if ($statement !== null) {
                //statement.close();
                $wrappedStatement = $statement->getWrappedStatement();
                if (method_exists($wrappedStatement, 'closeCursor')) {
                    $wrappedStatement->closeCursor();
                }
            }
        } catch (\Throwable $e) {
            //ignore
        }
    }

    protected function generateKeys($parameter): void
    {
        $keyGenerator = $this->mappedStatement->getKeyGenerator();
        $keyGenerator->processBefore($this->executor, $this->mappedStatement, null, $parameter);
    }
}
