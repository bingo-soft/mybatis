<?php

namespace Tests\DataSource;

use Doctrine\DBAL\Connection;
use MyBatis\DataSource\Unpooled\UnpooledDataSource;
use PHPUnit\Framework\TestCase;

class UnpooledDataSourceTest extends TestCase
{
    public function testSqliteUnpooledConnection(): void
    {
        $dataSource = new UnpooledDataSource("pdo_sqlite", null, null, null, ["database" => "database.sqlite"]);
        $this->assertTrue($dataSource->getConnection() instanceof Connection);
        $dataSource->getConnection()->close();
    }
}
