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

    public function openSession(Connection $connection): SqlSessionInterface
    {
        return $this->openSessionFromConnection($configuration->getDefaultExecutorType(), $connection);
    }

    public function getConfiguration(): Configuration
    {
        return $this->configuration;
    }

    private function openSessionFromConnection(string $execType, Connection $connection): SqlSessionInterface
    {
        try {
            $autoCommit = true;
            try {
                $autoCommit = $connection->isAutoCommit();
            } catch (\Exception $e) {
                // Failover to true, as most poor drivers
                // or databases won't support transactions
                $autoCommit = true;
            }
            $environment = $configuration->getEnvironment();
            $transactionFactory = $this->getTransactionFactoryFromEnvironment($environment);
            $tx = $transactionFactory->newTransaction($connection);
            $executor = $configuration->newExecutor($tx, $execType);
            return new DefaultSqlSession($configuration, $executor, $autoCommit);
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
