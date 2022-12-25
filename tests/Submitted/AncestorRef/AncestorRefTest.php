<?php

namespace Tests\Submitted\AncestorRef;

use MyBatis\Io\Resources;
use MyBatis\Session\{
    Configuration,
    SqlSessionFactoryInterface,
    SqlSessionFactoryBuilder
};
use PHPUnit\Framework\TestCase;
use Tests\BaseDataTest;

class AncestorRefTest extends TestCase
{
    private static $sqlSessionFactory;

    public static function setUpBeforeClass(): void
    {
        $stream = Resources::getResourceAsStream("tests/Resources/Submitted/AncestorRef/MapperConfig.xml");
        self::$sqlSessionFactory = (new SqlSessionFactoryBuilder())->build($stream);

        BaseDataTest::runScript(
            self::$sqlSessionFactory->getConfiguration()->getEnvironment()->getDataSource(),
            "tests/Resources/Submitted/AncestorRef/CreateDB.sql"
        );
    }

    public function testCircularAssociation(): void
    {
        try {
            $sqlSession = self::$sqlSessionFactory->openSession();
            $mapper = $sqlSession->getMapper(Mapper::class);
            $user = $mapper->getUserAssociation(1);
            $this->assertEquals("User2", $user->getFriend()->getName());
        } finally {
            $sqlSession->close();
        }
    }

    public function testCircularCollection(): void
    {
        try {
            $sqlSession = self::$sqlSessionFactory->openSession();
            $mapper = $sqlSession->getMapper(Mapper::class);
            $user = $mapper->getUserCollection(2);
            $this->assertEquals("User2", $user->getFriends()[0]->getName());
            $this->assertEquals("User3", $user->getFriends()[1]->getName());
        } finally {
            $sqlSession->close();
        }
    }

    public function testGetAncestorRefPermissions(): void
    {
        try {
            $sqlSession = self::$sqlSessionFactory->openSession();
            $mapper = $sqlSession->getMapper(Mapper::class);
            $blog = $mapper->selectBlog(1);
            $this->assertEquals("Author1", $blog->getAuthor()->getName());
            $this->assertEquals("Author2", $blog->getCoAuthor()->getName());
            $this->assertCount(2, $blog->getAuthor()->getPermissions());

            // author and coauthor should have a ref to blog
            $this->assertEquals($blog, $blog->getAuthor()->getBlog());
            $this->assertEquals($blog, $blog->getCoAuthor()->getBlog());
            // reputation should point to it author? or fail but do not point to a random one
            $this->assertEquals($blog->getAuthor(), $blog->getAuthor()->getReputation()->getAuthor());
            $this->assertEquals($blog->getCoAuthor(), $blog->getCoAuthor()->getReputation()->getAuthor());
        } finally {
            $sqlSession->close();
        }
    }
}
