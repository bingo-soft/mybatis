<?php

namespace MyBatis\Transaction\Dbal;

use Doctrine\DBAL\{
    Connection,
    TransactionIsolationLevel
};
use MyBatis\DataSource\DataSourceInterface;
use MyBatis\Transaction\{
    TransactionInterface,
    TransactionException
};

class DbalTransaction implements TransactionInterface
{
    protected $connection;
    protected $dataSource;
    protected $level;
    protected $autoCommit = false;
    protected $skipSetAutoCommitOnClose = false;

    public function __construct(/*Connection|DataSourceInterface*/$connOrDatasource, int $desiredLevel = null, bool $desiredAutoCommit = true, bool $skipSetAutoCommitOnClose = false)
    {
        if ($connOrDatasource instanceof Connection) {
            $this->connection = $connOrDatasource;
        } elseif ($connOrDatasource instanceof DataSourceInterface) {
            $this->dataSource = $connOrDatasource;
        }
        $this->level = $desiredLevel;
        $this->autoCommit = $desiredAutoCommit;
        $this->skipSetAutoCommitOnClose = $skipSetAutoCommitOnClose;
        //difference from Java MyBatis
        $this->getConnection()->beginTransaction();
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
        if ($this->connection !== null && !$this->connection->isAutoCommit()) {
            $this->connection->commit();
        }
    }

    public function rollback(): void
    {
        if ($this->connection !== null && !$this->connection->isAutoCommit() && $this->connection->isTransactionActive()) {
            $this->connection->rollBack();
        }
    }

    public function close(): void
    {
        if ($this->connection !== null) {
            $this->resetAutoCommit();
            $this->connection->close();
        }
    }

    protected function setDesiredAutoCommit(bool $desiredAutoCommit): void
    {
        try {
            if ($this->connection->isAutoCommit() !== $desiredAutoCommit) {
                $this->connection->setAutoCommit($desiredAutoCommit);
            }
        } catch (\Exception $e) {
            // Only a very poorly implemented driver would fail here,
            // and there's not much we can do about that.
            throw new TransactionException(
                "Error configuring AutoCommit.  "
                . "Your driver may not support getAutoCommit() or setAutoCommit(). "
                . "Requested setting: " . $desiredAutoCommit . ".  Cause: " . $e->getMessage()
            );
        }
    }

    protected function resetAutoCommit(): void
    {
        try {
            if (!$this->skipSetAutoCommitOnClose && !$this->connection->isAutoCommit()) {
                // MyBatis does not call commit/rollback on a connection if just selects were performed.
                // Some databases start transactions with select statements
                // and they mandate a commit/rollback before closing the connection.
                // A workaround is setting the autocommit to true before closing the connection.
                // Sybase throws an exception here.
                $this->connection->setAutoCommit(true);
            }
        } catch (\Exception $e) {
            //
        }
    }

    protected function openConnection(): void
    {
        $this->connection = $this->dataSource->getConnection();
        if ($this->level !== null) {
            $this->connection->setTransactionIsolation($this->level);
        }
        $this->setDesiredAutoCommit($this->autoCommit);
    }

    public function getTimeout(): ?int
    {
        return null;
    }
}
