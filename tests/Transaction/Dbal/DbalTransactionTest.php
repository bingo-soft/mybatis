<?php

namespace Tests\Transaction\Dbal;

use Doctrine\DBAL\{
    Connection,
    DriverManager
};
use PHPUnit\Framework\TestCase;
use MyBatis\DataSource\DataSourceInterface;
use MyBatis\Transaction\Dbal\DbalTransaction;

class DbalTransactionTest extends TestCase
{
    public function testSetAutoCommitOnClose(): void
    {
        $this->testAutoCommit(true, false, true, false);
        $this->testAutoCommit(false, false, true, false);
        $this->testAutoCommit(true, true, true, false);
        $this->testAutoCommit(false, true, true, false);
        $this->testAutoCommit(true, false, false, true);
        $this->testAutoCommit(false, false, false, true);
        $this->testAutoCommit(true, true, true, true);
        $this->testAutoCommit(false, true, true, true);
    }

    private function testAutoCommit(bool $initialAutoCommit, bool $desiredAutoCommit, bool $resultAutoCommit, bool $skipSetAutoCommitOnClose): void
    {
        $con = DriverManager::getConnection(["driver" => "pdo_sqlite"]);
        $con->setAutoCommit($initialAutoCommit);
        $ds = $this->createMock(DataSourceInterface::class);
        $ds->method('getConnection')->willReturn($con);

        $transaction = new DbalTransaction($ds, 1, $desiredAutoCommit, $skipSetAutoCommitOnClose);
        $transaction->getConnection();
        $transaction->commit();
        $transaction->close();

        $this->assertEquals($resultAutoCommit, $con->isAutoCommit());
    }
}
