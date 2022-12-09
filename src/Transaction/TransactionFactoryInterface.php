<?php

namespace MyBatis\Transaction;

use Doctrine\DBAL\{
    Connection,
    TransactionIsolationLevel
};
use MyBatis\DataSource\DataSourceInterface;

interface TransactionFactoryInterface
{
    /**
     * Sets transaction factory custom properties.
     * @param props
     *          the new properties
     */
    public function setProperties(array $props): void;

    /**
     * Creates a {@link Transaction} out of an existing connection.
     * @param conn Existing database connection
     * @return Transaction
     */
    public function newTransaction(/*Connection|DataSourceInterface*/$connOrSource, int $level = null, ?bool $autoCommit = false): TransactionInterface;
}
