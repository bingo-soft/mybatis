<?php

namespace MyBatis\Executor\Statement;

use Doctrine\DBAL\{
    Connection,
    Statement
};
use MyBatis\Cursor\CursorInterface;
use MyBatis\Executor\Parameter\ParameterHandlerInterface;
use MyBatis\Mapping\BoundSql;
use MyBatis\Session\ResultHandlerInterface;

interface StatementHandlerInterface
{
    public function prepare(Connection $connection, int $transactionTimeout): Statement;
  
    public function parameterize(Statement $statement): void;
  
    //public function batch(Statement $statement): void;
  
    public function update(/*Statement $statement*/): int;
  
    public function query(/*Statement $statement, */ResultHandlerInterface $resultHandler): array;
  
    public function queryCursor(Statement $statement): CursorInterface;
  
    public function getBoundSql(): BoundSql;

    public function getParameterHandler(): ParameterHandlerInterface;  
}
