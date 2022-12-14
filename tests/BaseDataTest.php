<?php

namespace Tests;

use MyBatis\DataSource\DataSourceInterface;
use MyBatis\DataSource\Unpooled\UnpooledDataSource;

abstract class BaseDataTest
{
    //public const BLOG_PROPERTIES = "tests/Databases/blog/blog-derby.properties";
    public const BLOG_DDL = "tests/Resources/Databases/Blog/postgres-blog-derby-schema.sql";
    public const BLOG_DATA = "tests/Resources/Databases/Blog/postgres-blog-derby-dataload.sql";
    private static $dataSource;

    public static function createUnpooledDataSource(string $database): UnpooledDataSource
    {
        if (self::$dataSource === null) {
            self::$dataSource = new UnpooledDataSource("pdo_pgsql", "pgsql:host=localhost;port=5432;dbname=$database;", "postgres", "postgres");
        }
        return self::$dataSource;
    }

    public static function runScript(DataSourceInterface $ds, string $resource): void
    {
        $connection = $ds->getConnection();
        $commands = explode(';', file_get_contents($resource));
        foreach ($commands as $command) {
            $sql = trim($command);
            if (!empty($sql)) {
                $connection->executeQuery($sql);
            }
        }
    }

    public static function createBlogDataSource(): DataSourceInterface
    {
        $ds = self::createUnpooledDataSource('blog');
        $isAutoCommit = $ds->getConnection()->isAutoCommit();
        try {
            $ds->setAutoCommit(true);
            self::runScript($ds, self::BLOG_DDL);
            self::runScript($ds, self::BLOG_DATA);
        } catch (\Exception $e) {
            //ignore
        } finally {
            $ds->setAutoCommit($isAutoCommit);
        }
        return $ds;
    }
}
