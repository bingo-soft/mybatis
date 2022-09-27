<?php

namespace Tests\Transaction\Dbal;

use Doctrine\DBAL\{
    Connection,
    DriverManager
};
use PHPUnit\Framework\TestCase;
use MyBatis\DataSource\DataSourceInterface;
use MyBatis\Transaction\Dbal\{
    DbalTransaction,
    DbalTransactionFactory
};

class DbalTransactionFactoryTest extends TestCase
{
    public function testNullProperties(): void
    {
        $connection = DriverManager::getConnection(["driver" => "pdo_sqlite"]);
        $connection->setAutoCommit(false);
        $factory = new DbalTransactionFactory();
        $factory->setProperties(null);
        $transaction = $factory->newTransaction($connection);
        $transaction->getConnection();
        $transaction->close();
        $this->assertTrue($connection->isAutoCommit());
    }

    public function testSkipSetAutoCommitOnClose(): void
    {
        $connection = DriverManager::getConnection(["driver" => "pdo_sqlite"]);
        $connection->setAutoCommit(false);
        $ds = $this->createMock(DataSourceInterface::class);
        $ds->method('getConnection')->willReturn($connection);

        $factory = new DbalTransactionFactory();
        $properties = [];
        $properties["skipSetAutoCommitOnClose"] = "true";
        $factory->setProperties($properties);
        $transaction = $factory->newTransaction($ds, 1, false);
        $transaction->getConnection();
        $transaction->close();
        $this->assertFalse($connection->isAutoCommit());
    }
}
