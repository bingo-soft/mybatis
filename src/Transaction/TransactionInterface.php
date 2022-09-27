<?php

namespace MyBatis\Transaction;

use Doctrine\DBAL\Connection;

interface TransactionInterface
{
    /**
     * Retrieve inner database connection.
     * @return DataBase connection
     */
    public function getConnection(): Connection;

    /**
     * Commit inner database connection.
     */
    public function commit(): void;

    /**
     * Rollback inner database connection.
     */
    public function rollback(): void;

    /**
     * Close inner database connection.
     */
    public function close(): void;

    /**
     * Get transaction timeout if set.
     *
     * @return the timeout
     */
    public function getTimeout(): ?int;
}
