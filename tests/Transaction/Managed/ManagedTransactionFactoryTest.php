<?php

namespace Tests\Transaction\Managed;

use Doctrine\DBAL\{
    Connection,
    DriverManager
};
use PHPUnit\Framework\TestCase;
use MyBatis\Transaction\{
    TransactionInterface,
    TransactionFactoryInterface
};
use MyBatis\Transaction\Managed\{
    ManagedTransactionFactory
};

class ManagedTransactionFactoryTest extends TestCase
{
    public function testShouldEnsureThatCallsToManagedTransactionAPIDoNotForwardToManagedConnections(): void
    {
        $conn = $this->createMock(Connection::class);
        $tf = new ManagedTransactionFactory();
        $tf->setProperties([]);
        $tx = $tf->newTransaction($conn);
        $this->assertEquals($conn, $tx->getConnection());
        $tx->commit();
        $tx->rollback();
        $conn->expects($this->once())->method('close');
        $tx->close();
    }

    public function testShouldEnsureThatCallsToManagedTransactionAPIDoNotForwardToManagedConnectionsAndDoesNotCloseConnection(): void
    {
        $conn = $this->createMock(Connection::class);
        $tf = new ManagedTransactionFactory();
        $props = [];
        $props["closeConnection"] = "false";
        $tf->setProperties($props);
        $tx = $tf->newTransaction($conn);
        $this->assertEquals($conn, $tx->getConnection());
        $tx->commit();
        $tx->rollback();
        $conn->expects($this->never())->method('close');
        $conn->expects($this->never())->method($this->anything());
        $tx->close();
    }
}
