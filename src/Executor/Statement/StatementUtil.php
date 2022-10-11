<?php

namespace MyBatis\Executor\Statement;

use Doctrine\DBAL\Statement;

class StatementUtil
{
    public static function applyTransactionTimeout(Statement $statement, ?int $queryTimeout, ?int $transactionTimeout): void
    {
        if ($transactionTimeout === null) {
            return;
        }
        if ($queryTimeout === null || $queryTimeout === 0 || $transactionTimeout < $queryTimeout) {
            //statement.setQueryTimeout(transactionTimeout);
        }
    }
}
