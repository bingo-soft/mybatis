<?php

namespace MyBatis\Transaction\Managed;

use Doctrine\DBAL\{
    Connection,
    TransactionIsolationLevel
};
use MyBatis\DataSource\DataSourceInterface;
use MyBatis\Transaction\{
    TransactionInterface,
    TransactionException
};

class ManagedTransaction implements TransactionInterface
{
    private $dataSource;
    private $level;
    private $connection;
    private $closeConnection;

    public function __construct(/*Connection|DataSourceInterface*/$connOrSource, /*bool|int*/$closeorLevel, bool $closeConnection = false)
    {
        if ($connOrSource instanceof Connection) {
            $this->connection = $connOrSource;
        } elseif ($connOrSource instanceof DataSourceInterface) {
            $this->dataSource = $connOrSource;
        }
        if (is_bool($closeorLevel)) {
            $this->closeConnection = $closeorLevel;
        } elseif (is_int($closeorLevel)) {
            $this->level = $closeorLevel;
        }
        if ($this->closeConnection === null && is_bool($closeConnection)) {
            $this->closeConnection = $closeConnection;
        }
    }

    public function getConnection(): Connection
    {
        if ($this->connection === null) {
            $this->openConnection();
        }
        return $this->connection;
    }

    public function commit(): void
    {
        // Does nothing
    }

    public function rollback(): void
    {
        // Does nothing
    }

    public function close(): void
    {
        if ($this->closeConnection && $this->connection != null) {
            $this->connection->close();
        }
    }

    protected function openConnection(): void
    {
        $this->connection = $this->dataSource->getConnection();
        if ($this->level !== null) {
            $this->connection->setTransactionIsolation($this->level);
        }
    }

    public function getTimeout(): ?int
    {
        return null;
    }
}
