<?php

namespace MyBatis\Transaction\Managed;

use Doctrine\DBAL\{
    Connection,
    TransactionIsolationLevel
};
use MyBatis\DataSource\DataSourceInterface;
use MyBatis\Parsing\Boolean;
use MyBatis\Transaction\{
    TransactionInterface,
    TransactionException,
    TransactionFactoryInterface
};

class ManagedTransactionFactory implements TransactionFactoryInterface
{
    private $closeConnection = true;

    public function setProperties(array $props): void
    {
        if (!empty($props)) {
            if (array_key_exists("closeConnection", $props)) {
                $closeConnectionProperty = $props["closeConnection"];
                $this->closeConnection = Boolean::parseBoolean($closeConnectionProperty);
            }
        }
    }

    public function newTransaction(/*Connection|DataSourceInterface*/$connOrSource, int $level = null, bool $autoCommit = true): TransactionInterface
    {
        // Silently ignores autocommit and isolation level, as managed transactions are entirely
        // controlled by an external manager.  It's silently ignored so that
        // code remains portable between managed and unmanaged configurations.
        if ($connOrSource instanceof Connection) {
            return new ManagedTransaction($connOrSource, $this->closeConnection);
        } else {
            return new ManagedTransaction($connOrSource, $level, $this->closeConnection);
        }
    }
}
