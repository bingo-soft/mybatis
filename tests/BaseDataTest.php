<?php

namespace Tests;

use MyBatis\DataSource\DataSourceInterface;
use MyBatis\DataSource\Unpooled\UnpooledDataSource;

abstract class BaseDataTest
{
    //public const BLOG_PROPERTIES = "tests/Databases/blog/blog-derby.properties";
    public const BLOG_DDL = "tests/Databases/Blog/postgres-blog-derby-schema.sql";
    public const BLOG_DATA = "tests/Databases/Blog/postgres-blog-derby-dataload.sql";

    public static function createUnpooledDataSource(string $database): UnpooledDataSource
    {
        $dataSource = new UnpooledDataSource("pdo_pgsql", "pgsql:host=localhost;port=5432;dbname=blog;", "postgres", "postgres");
        return $dataSource;
    }

    public static function runScript(DataSourceInterface $ds, string $resource): void
    {
        $connection = $ds->getConnection();
        $commands = explode(';', file_get_contents($resource));
        $connection->beginTransaction();
        foreach ($commands as $command) {
            $sql = trim($command);
            if (!empty($sql)) {
                $connection->executeQuery($sql);
            }
        }
        $connection->commit();
    }

    public static function createBlogDataSource(): DataSourceInterface
    {
        $ds = self::createUnpooledDataSource('blog');
        self::runScript($ds, self::BLOG_DDL);
        self::runScript($ds, self::BLOG_DATA);
        return $ds;
    }
}
