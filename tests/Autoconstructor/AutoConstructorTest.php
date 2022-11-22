<?php

namespace Tests\Autoconstructor;

use MyBatis\Exception\PersistenceException;
use MyBatis\Executor\ExecutorException;
use MyBatis\Io\Resources;
use MyBatis\Session\{
    SqlSessionInterface,
    SqlSessionFactoryInterface,
    SqlSessionFactoryBuilder
};
use PHPUnit\Framework\TestCase;
use Tests\BaseDataTest;
use MyBatis\Type\SomeClass;

class AutoConstructorTest extends TestCase
{
    private static $sqlSessionFactory;

    public function setUp(): void
    {
        // create a SqlSessionFactory
        if (self::$sqlSessionFactory === null) {
            $reader = Resources::getResourceAsStream("tests/Resources/Autoconstructor/mybatis-config.xml");
            self::$sqlSessionFactory = (new SqlSessionFactoryBuilder())->build($reader);
        }

        BaseDataTest::runScript(
            self::$sqlSessionFactory->getConfiguration()->getEnvironment()->getDataSource(),
            "tests/Resources/Autoconstructor/CreateDB.sql"
        );
    }

    public function testFullyPopulatedSubject(): void
    {
        $sqlSession = self::$sqlSessionFactory->openSession();
        $mapper = $sqlSession->getMapper(AutoConstructorMapper::class);
        $subject = $mapper->getSubject(1);
        $this->assertEquals(1, $subject);
        $sqlSession->close();
    }

    public function testAnnotatedSubject(): void
    {
        $sqlSession = self::$sqlSessionFactory->openSession();
        $mapper = $sqlSession->getMapper(AutoConstructorMapper::class);
        $subjects = $mapper->getAnnotatedSubjects();
        $this->assertEquals([1, 2, 2], $subjects);
        $sqlSession->close();
    }
}
