<?php

namespace MyBatis\Transaction\Dbal;

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

class DbalTransactionFactory implements TransactionFactoryInterface
{
    private $skipSetAutoCommitOnClose = false;

    public function setProperties(?array $props): void
    {
        if (empty($props)) {
            return;
        }
        if (array_key_exists("skipSetAutoCommitOnClose", $props)) {
            $value = $props["skipSetAutoCommitOnClose"];
            $this->skipSetAutoCommitOnClose = Boolean::parseBoolean($value);
        }
    }

    public function newTransaction(/*Connection|DataSourceInterface*/$connOrSource, int $level = null, ?bool $autoCommit = false): TransactionInterface
    {
        return new DbalTransaction($connOrSource, $level, $autoCommit, $this->skipSetAutoCommitOnClose);
    }
}
