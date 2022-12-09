<?php

namespace MyBatis\Session\Defaults;

use Doctrine\DBAL\Connection;
use MyBatis\Mapping\Environment;
use MyBatis\Session\{
    Configuration,
    ExecutorType,
    SqlSessionInterface,
    SqlSessionFactoryInterface
};
use MyBatis\Transaction\{
    TransactionFactoryInterface,
    TransactionInterface
};
use MyBatis\Transaction\Managed\ManagedTransactionFactory;

class DefaultSqlSessionFactory implements SqlSessionFactoryInterface
{
    private $configuration;

    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    public function openSession(/*?Connection*/$connectionOrIsolationLevel = null): SqlSessionInterface
    {
        $connection = $connectionOrIsolationLevel;
        if ($connectionOrIsolationLevel === null || is_int($connectionOrIsolationLevel)) {
            $ds = $this->configuration->getEnvironment()->getDataSource();
            $connection = $this->configuration->getEnvironment()->getDataSource()->getConnection();
            /*if ($ds->isAutoCommit() != $connection->isAutoCommit()) {
                $connection->setAutoCommit($ds->isAutoCommit());
            }*/
        }
        if (is_int($connectionOrIsolationLevel)) {
            $connection->setTransactionIsolation($connectionOrIsolationLevel);
        }
        $session = $this->openSessionFromConnection($this->configuration->getDefaultExecutorType(), $connection);

        return $session;
    }

    public function getConfiguration(): Configuration
    {
        return $this->configuration;
    }

    private function openSessionFromConnection(string $execType, Connection $connection): SqlSessionInterface
    {
        try {
            $autoCommit = false;
            try {
                $autoCommit = $connection->isAutoCommit();
            } catch (\Exception $e) {
                // Failover to true, as most poor drivers
                // or databases won't support transactions
                $autoCommit = false;
            }
            $environment = $this->configuration->getEnvironment();
            $transactionFactory = $this->getTransactionFactoryFromEnvironment($environment);
            $tx = $transactionFactory->newTransaction($connection);
            $executor = $this->configuration->newExecutor($tx, $execType);
            return new DefaultSqlSession($this->configuration, $executor, $autoCommit);
        } catch (\Exception $e) {
            throw new \Exception("Error opening session.  Cause: " . $e->getMessage());
        } finally {
        }
    }

    private function getTransactionFactoryFromEnvironment(?Environment $environment = null): TransactionFactoryInterface
    {
        if ($environment === null || $environment->getTransactionFactory() === null) {
            return new ManagedTransactionFactory();
        }
        return $environment->getTransactionFactory();
    }

    private function closeTransaction(?TransactionInterface $tx = null): void
    {
        if ($tx !== null) {
            try {
                $tx->close();
            } catch (\Exception $ignore) {
                // Intentionally ignore. Prefer previous error.
            }
        }
    }
}
