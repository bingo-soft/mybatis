<?php

namespace Tests\Submitted\OgnlEnum;

use MyBatis\Io\Resources;
use MyBatis\Session\{
    Configuration,
    SqlSessionFactoryInterface,
    SqlSessionFactoryBuilder
};
use PHPUnit\Framework\TestCase;
use Tests\BaseDataTest;

class EnumWithOgnlTest extends TestCase
{
    private static $sqlSessionFactory;

    public static function setUpBeforeClass(): void
    {
        $stream = Resources::getResourceAsStream("tests/Resources/Submitted/OgnlEnum/ibatisConfig.xml");
        self::$sqlSessionFactory = (new SqlSessionFactoryBuilder())->build($stream);

        BaseDataTest::runScript(
            self::$sqlSessionFactory->getConfiguration()->getEnvironment()->getDataSource(),
            "tests/Resources/Submitted/OgnlEnum/CreateDB.sql"
        );
    }

    public function testEnumWithOgnl(): void
    {
        try {
            $sqlSession = self::$sqlSessionFactory->openSession();
            $personMapper = $sqlSession->getMapper(PersonMapper::class);
            $persons = $personMapper->selectAllByType(null);
            $this->assertCount(3, $persons);
        } finally {
            $sqlSession->close();
        }
    }

    public function testEnumWithOgnlDirector(): void
    {
        try {
            $sqlSession = self::$sqlSessionFactory->openSession();
            $personMapper = $sqlSession->getMapper(PersonMapper::class);
            $persons = $personMapper->selectAllByType(Type::DIRECTOR);
            $this->assertCount(1, $persons);
        } finally {
            $sqlSession->close();
        }
    }
}
