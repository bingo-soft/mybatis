<?php

namespace Tests\Submitted\OgnlStatic;

use MyBatis\Io\Resources;
use MyBatis\Session\SqlSessionFactoryBuilder;
use PHPUnit\Framework\TestCase;
use Tests\BaseDataTest;

class OgnlStaticTest extends TestCase
{
    private static $sqlSessionFactory;

    public static function setUpBeforeClass(): void
    {
        $stream = Resources::getResourceAsStream("tests/Resources/Submitted/OgnlStatic/mybatis-config.xml");
        self::$sqlSessionFactory = (new SqlSessionFactoryBuilder())->build($stream);
        BaseDataTest::runScript(
            self::$sqlSessionFactory->getConfiguration()->getEnvironment()->getDataSource(),
            "tests/Resources/Submitted/OgnlStatic/CreateDB.sql"
        );
    }

    public function testShouldGetAUserStatic(): void
    {
        try {
            $session = self::$sqlSessionFactory->openSession();
            $mapper = $session->getMapper(Mapper::class);
            $user = $mapper->getUserStatic(1);
            $this->assertNotNull($user);
            $this->assertEquals("User1", $user->getName());
        } finally {
            $session->close();
        }
    }

    public function testShouldGetAUserWithIfNode(): void
    {
        try {
            $session = self::$sqlSessionFactory->openSession();
            $mapper = $session->getMapper(Mapper::class);
            $user = $mapper->getUserIfNode("User2");
            $this->assertNull($user);
            $user = $mapper->getUserIfNode("User1");
            $this->assertNotNull($user);
            $this->assertEquals("User1", $user->getName());
        } finally {
            $session->close();
        }
    }
}
