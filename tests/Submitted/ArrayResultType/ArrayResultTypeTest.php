<?php

namespace Tests\Submitted\ArrayResultType;

use MyBatis\Io\Resources;
use MyBatis\Session\{
    Configuration,
    SqlSessionFactoryInterface,
    SqlSessionFactoryBuilder
};
use PHPUnit\Framework\TestCase;
use Tests\BaseDataTest;

class ArrayResultTypeTest extends TestCase
{
    private static $sqlSessionFactory;

    public static function setUpBeforeClass(): void
    {
        $stream = Resources::getResourceAsStream("tests/Resources/Submitted/ArrayResultType/MapperConfig.xml");
        self::$sqlSessionFactory = (new SqlSessionFactoryBuilder())->build($stream);

        BaseDataTest::runScript(
            self::$sqlSessionFactory->getConfiguration()->getEnvironment()->getDataSource(),
            "tests/Resources/Submitted/ArrayResultType/CreateDB.sql"
        );
    }

    public function testShouldGetUserArray(): void
    {
        try {
            $sqlSession = self::$sqlSessionFactory->openSession();
            $mapper = $sqlSession->getMapper(Mapper::class);
            $users = $mapper->getUsers();
            $this->assertEquals("User1", $users[0]->getName());
            $this->assertEquals("User2", $users[1]->getName());
        } finally {
            $sqlSession->close();
        }
    }

    public function testShouldGetUserArrayXml(): void
    {
        try {
            $sqlSession = self::$sqlSessionFactory->openSession();
            $mapper = $sqlSession->getMapper(Mapper::class);
            $users = $mapper->getUsersXml();
            $this->assertEquals("User1", $users[0]->getName());
            $this->assertEquals("User2", $users[1]->getName());
        } finally {
            $sqlSession->close();
        }
    }

    public function testShouldGetSimpleTypeArray(): void
    {
        try {
            $sqlSession = self::$sqlSessionFactory->openSession();
            $mapper = $sqlSession->getMapper(Mapper::class);
            $ids = $mapper->getUserIds();
            $this->assertEquals(1, $ids[0]);
        } finally {
            $sqlSession->close();
        }
    }

    public function testShouldGetPrimitiveArray(): void
    {
        try {
            $sqlSession = self::$sqlSessionFactory->openSession();
            $mapper = $sqlSession->getMapper(Mapper::class);
            $ids = $mapper->getUserIdsPrimitive();
            $this->assertEquals(1, $ids[0]);
        } finally {
            $sqlSession->close();
        }
    }
}
