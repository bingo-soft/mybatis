<?php

namespace Tests\Submitted\Includes;

use MyBatis\Io\Resources;
use MyBatis\Session\{
    Configuration,
    SqlSessionFactoryInterface,
    SqlSessionFactoryBuilder
};
use PHPUnit\Framework\TestCase;
use Tests\BaseDataTest;

class IncludeTest extends TestCase
{
    private static $sqlSessionFactory;

    public static function setUpBeforeClass(): void
    {
        $stream = Resources::getResourceAsStream("tests/Resources/Submitted/Includes/MapperConfig.xml");
        self::$sqlSessionFactory = (new SqlSessionFactoryBuilder())->build($stream);

        BaseDataTest::runScript(
            self::$sqlSessionFactory->getConfiguration()->getEnvironment()->getDataSource(),
            "tests/Resources/Submitted/Includes/CreateDB.sql"
        );
    }

    public function testIncludes(): void
    {
        try {
            $sqlSession = self::$sqlSessionFactory->openSession();
            $result = $sqlSession->selectOne("Tests\Submitted\Includes\Mapper.selectWithProperty");
            $this->assertEquals(1, $result);
        } finally {
            $sqlSession->close();
        }
    }
}
