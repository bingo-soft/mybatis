<?php

namespace Tests\Session;

use Doctrine\DBAL\TransactionIsolationLevel;
use MyBatis\Binding\BindingException;
use MyBatis\Cache\Impl\PerpetualCache;
use MyBatis\Exception\TooManyResultsException;
use MyBatis\Executor\Result\DefaultResultHandler;
use MyBatis\Io\Resources;
use MyBatis\Mapping\{
    MappedStatement,
    SqlCommandType,
    SqlSourceInterface
};
use MyBatis\Session\Defaults\DefaultSqlSessionFactory;
use MyBatis\Session\{
    Configuration,
    RowBounds,
    SqlSessionFactoryInterface,
    SqlSessionFactoryBuilder
};
use PHPUnit\Framework\TestCase;
use Tests\BaseDataTest;
use Tests\Domain\Blog\{
    Author,
    Blog,
    Comment,
    DraftPost,
    Post,
    Section,
    Tag
};
use Tests\Domain\Blog\Mappers\{
    AuthorMapper,
    AuthorMapperWithMultipleHandlers,
    AuthorMapperWithRowBounds,
    BlogMapper
};
use Util\Proxy\ProxyInterface;

class SqlSessionTest extends TestCase
{
    private static $sqlSessionFactory;

    public static function setUpBeforeClass(): void
    {
        $stream = Resources::getResourceAsStream("tests/Resources/Builder/MapperConfig.xml");
        self::$sqlSessionFactory = (new SqlSessionFactoryBuilder())->build($stream);

        BaseDataTest::createBlogDataSource();
    }

    public function testShouldResolveBothSimpleNameAndFullyQualifiedName(): void
    {
        $c = new Configuration();
        $fullName = "com.mycache.MyCache";
        $shortName = "MyCache";
        $cache = new PerpetualCache($fullName);
        $c->addCache($cache);
        $this->assertEquals($cache, $c->getCache($fullName));
        $this->assertEquals($cache, $c->getCache($shortName));
    }

    public function testShouldFailOverToMostApplicableSimpleName(): void
    {
        $c = new Configuration();
        $fullName = "com.mycache.MyCache";
        $invalidName = "unknown.namespace.MyCache";
        $cache = new PerpetualCache($fullName);
        $c->addCache($cache);
        $this->assertEquals($cache, $c->getCache($fullName));
        $this->expectException(\Exception::class);
        $c->getCache($invalidName);
    }

    public function testShouldSucceedWhenFullyQualifiedButFailDueToAmbiguity(): void
    {
        $c = new Configuration();

        $name1 = "com.mycache.MyCache";
        $cache1 = new PerpetualCache($name1);
        $c->addCache($cache1);

        $name2 = "com.other.MyCache";
        $cache2 = new PerpetualCache($name2);
        $c->addCache($cache2);

        $shortName = "MyCache";

        $this->assertEquals($cache1, $c->getCache($name1));
        $this->assertEquals($cache2, $c->getCache($name2));

        $d = $c->getCache($shortName);
        $this->assertEquals('MyBatis\Session\Ambiguity', get_class($d));
    }

    public function testShouldSelectAllAuthors(): void
    {
        try {
            $session = self::$sqlSessionFactory->openSession(TransactionIsolationLevel::SERIALIZABLE);
            $authors = $session->selectList("Tests\Domain\Blog\Mappers\AuthorMapper.selectAllAuthors");
            $this->assertEquals(2, count($authors));
        } finally {
            $session->close();
        }
    }

    public function testShouldFailWithTooManyResultsException(): void
    {
        try {
            $session = self::$sqlSessionFactory->openSession(TransactionIsolationLevel::SERIALIZABLE);
            $this->expectException(\Exception::class);
            $session->selectOne("Tests\Domain\Blog\Mappers\AuthorMapper.selectAllAuthors");
        } finally {
            $session->close();
        }
    }

    public function testShouldSelectAllAuthorsAsMap(): void
    {
        try {
            $session = self::$sqlSessionFactory->openSession(TransactionIsolationLevel::SERIALIZABLE);
            $authors = $session->selectMap("Tests\Domain\Blog\Mappers\AuthorMapper.selectAllAuthors", null, "id");
            $this->assertEquals(2, count($authors));
            foreach ($authors as $key => $author) {
                $this->assertEquals($key, $author->getId());
            }
        } finally {
            $session->close();
        }
    }

    public function testShouldSelectCountOfPosts(): void
    {
        try {
            $session = self::$sqlSessionFactory->openSession(TransactionIsolationLevel::SERIALIZABLE);
            $count = $session->selectOne("Tests\Domain\Blog\Mappers\BlogMapper.selectCountOfPosts");
            $this->assertEquals(5, $count);
        } finally {
            $session->close();
        }
    }

    public function testShouldEnsureThatBothEarlyAndLateResolutionOfNesteDiscriminatorsResolesToUseNestedResultSetHandler(): void
    {
        $configuration = self::$sqlSessionFactory->getConfiguration();
        $this->assertTrue($configuration->getResultMap("Tests\Domain\Blog\Mappers\BlogMapper.earlyNestedDiscriminatorPost")->hasNestedResultMaps());
        $this->assertTrue($configuration->getResultMap("Tests\Domain\Blog\Mappers\BlogMapper.lateNestedDiscriminatorPost")->hasNestedResultMaps());
    }

    public function testShouldSelectOneAuthor(): void
    {
        try {
            $session = self::$sqlSessionFactory->openSession(TransactionIsolationLevel::SERIALIZABLE);
            $author = $session->selectOne(
                "Tests\Domain\Blog\Mappers\AuthorMapper.selectAuthor",
                new Author(101)
            );
            $this->assertEquals(101, $author->getId());
            $this->assertEquals('NEWS', $author->getFavouriteSection());
        } finally {
            $session->close();
        }
    }

    public function testShouldSelectOneAuthorAsList(): void
    {
        try {
            $session = self::$sqlSessionFactory->openSession(TransactionIsolationLevel::SERIALIZABLE);
            $authors = $session->selectList(
                "Tests\Domain\Blog\Mappers\AuthorMapper.selectAuthor",
                new Author(101)
            );
            $this->assertEquals(101, $authors[0]->getId());
            $this->assertEquals('NEWS', $authors[0]->getFavouriteSection());
        } finally {
            $session->close();
        }
    }

    public function testShouldSelectOneAuthorWithInlineParams(): void
    {
        try {
            $session = self::$sqlSessionFactory->openSession(TransactionIsolationLevel::SERIALIZABLE);
            $author = $session->selectOne(
                "Tests\Domain\Blog\Mappers\AuthorMapper.selectAuthorWithInlineParams",
                new Author(101)
            );
            $this->assertEquals(101, $author->getId());
        } finally {
            $session->close();
        }
    }

    public function testShouldInsertAuthor(): void
    {
        try {
            $session = self::$sqlSessionFactory->openSession(TransactionIsolationLevel::SERIALIZABLE);
            $expected = new Author(500, "cbegin", "******", "cbegin@somewhere.com", "Something...", null);
            $updates = $session->insert("Tests\Domain\Blog\Mappers\AuthorMapper.insertAuthor", $expected);
            $this->assertEquals(1, $updates);
            $actual = $session->selectOne("Tests\Domain\Blog\Mappers\AuthorMapper.selectAuthor", new Author(500));
            $this->assertNotNull($actual);
            $this->assertEquals($expected->getId(), $actual->getId());
            $this->assertEquals($expected->getUsername(), $actual->getUsername());
            $this->assertEquals($expected->getPassword(), $actual->getPassword());
            $this->assertEquals($expected->getEmail(), $actual->getEmail());
            $this->assertEquals($expected->getBio(), $actual->getBio());
        } finally {
            $session->close();
        }
    }

    public function testShouldUpdateAuthorImplicitRollback(): void
    {
        try {
            $session = self::$sqlSessionFactory->openSession(TransactionIsolationLevel::SERIALIZABLE);
            $original = $session->selectOne("Tests\Domain\Blog\Mappers\AuthorMapper.selectAuthor", 101);
            $original->setEmail("new@email.com");
            $updates = $session->update("Tests\Domain\Blog\Mappers\AuthorMapper.updateAuthor", $original);
            $this->assertEquals(1, $updates);
            $updated = $session->selectOne("Tests\Domain\Blog\Mappers\AuthorMapper.selectAuthor", 101);
            $this->assertEquals($original->getEmail(), $updated->getEmail());
        } finally {
            $session->close();
        }
        try {
            $session = self::$sqlSessionFactory->openSession(TransactionIsolationLevel::SERIALIZABLE);
            $updated = $session->selectOne("Tests\Domain\Blog\Mappers\AuthorMapper.selectAuthor", 101);
            $this->assertEquals("jim@ibatis.apache.org", $updated->getEmail());
        } finally {
            $session->close();
        }
    }

    public function testShouldUpdateAuthorCommit(): void
    {
        $original = null;
        try {
            $session = self::$sqlSessionFactory->openSession(TransactionIsolationLevel::SERIALIZABLE);
            $original = $session->selectOne("Tests\Domain\Blog\Mappers\AuthorMapper.selectAuthor", 101);
            $original->setEmail("new@email.com");
            $updates = $session->update("Tests\Domain\Blog\Mappers\AuthorMapper.updateAuthor", $original);
            $this->assertEquals(1, $updates);
            $updated = $session->selectOne("Tests\Domain\Blog\Mappers\AuthorMapper.selectAuthor", 101);
            $this->assertEquals($original->getEmail(), $updated->getEmail());
            $session->commit();
        } finally {
            $session->close();
        }
        try {
            $session = self::$sqlSessionFactory->openSession(TransactionIsolationLevel::SERIALIZABLE);
            $updated = $session->selectOne("Tests\Domain\Blog\Mappers\AuthorMapper.selectAuthor", 101);
            $this->assertEquals($original->getEmail(), $updated->getEmail());
        } finally {
            $session->close();
        }
    }

    //@TODO
    private function testShouldUpdateAuthorIfNecessary(): void
    {
        $original = null;
        try {
            $session = self::$sqlSessionFactory->openSession(TransactionIsolationLevel::SERIALIZABLE);
            $original = $session->selectOne("Tests\Domain\Blog\Mappers\AuthorMapper.selectAuthor", 101);
            $original->setEmail("new@email.com");
            $original->setBio(null);
            $updates = $session->update("Tests\Domain\Blog\Mappers\AuthorMapper.updateAuthorIfNecessary", $original);
            $this->assertEquals(1, $updates);
            $updated = $session->selectOne("Tests\Domain\Blog\Mappers\AuthorMapper.selectAuthor", 101);
            $this->assertEquals($original->getEmail(), $updated->getEmail());
            $session->commit();
        } finally {
            $session->close();
        }
        try {
            $session = self::$sqlSessionFactory->openSession();
            $updated = $session->selectOne("Tests\Domain\Blog\Mappers\AuthorMapper.selectAuthor", 101);
            $this->assertEquals($original->getEmail(), $updated->getEmail());
        } finally {
            $session->close();
        }
    }

    public function testShouldDeleteAuthor(): void
    {
        try {
            $session = self::$sqlSessionFactory->openSession(TransactionIsolationLevel::SERIALIZABLE);
            $id = 102;

            $authors = $session->selectList("Tests\Domain\Blog\Mappers\AuthorMapper.selectAuthor", $id);
            $this->assertEquals(1, count($authors));

            $updates = $session->delete("Tests\Domain\Blog\Mappers\AuthorMapper.deleteAuthor", $id);
            $this->assertEquals(1, $updates);

            $authors = $session->selectList("Tests\Domain\Blog\Mappers\AuthorMapper.selectAuthor", $id);
            $this->assertEquals(0, count($authors));

            $session->rollback();
            $authors = $session->selectList("Tests\Domain\Blog\Mappers\AuthorMapper.selectAuthor", $id);
            $this->assertEquals(1, count($authors));
        } finally {
            $session->close();
        }
    }

    public function testShouldSelectBlogWithPostsAndAuthorUsingSubSelects(): void
    {
        try {
            $session = self::$sqlSessionFactory->openSession(TransactionIsolationLevel::SERIALIZABLE);
            $blog = $session->selectOne("Tests\Domain\Blog\Mappers\BlogMapper.selectBlogWithPostsUsingSubSelect", 1);
            $this->assertEquals("Jim Business", $blog->getTitle());
            $this->assertEquals(2, count($blog->getPosts()));
            $this->assertEquals("Corn nuts", $blog->getPosts()[0]->getSubject());
            $this->assertEquals(101, $blog->getAuthor()->getId());
            $this->assertEquals("jim", $blog->getAuthor()->getUsername());
        } finally {
            $session->close();
        }
    }

    public function testShouldSelectBlogWithPostsAndAuthorUsingSubSelectsLazily(): void
    {
        try {
            $session = self::$sqlSessionFactory->openSession();
            $blog = $session->selectOne("Tests\Domain\Blog\Mappers\BlogMapper.selectBlogWithPostsUsingSubSelectLazily", 1);
            $this->assertTrue($blog instanceof ProxyInterface);
            $this->assertEquals("Jim Business", $blog->getTitle());
            $this->assertEquals(2, count($blog->getPosts()));
            $this->assertEquals("Corn nuts", $blog->getPosts()[0]->getSubject());
            $this->assertEquals(101, $blog->getAuthor()->getId());
            $this->assertEquals("jim", $blog->getAuthor()->getUsername());
        } finally {
            $session->close();
        }
    }

    public function testShouldSelectBlogWithPostsAndAuthorUsingJoin(): void
    {
        try {
            $session = self::$sqlSessionFactory->openSession();
            $blog = $session->selectOne("Tests\Domain\Blog\Mappers\BlogMapper.selectBlogJoinedWithPostsAndAuthor", 1);
            $this->assertEquals("Jim Business", $blog->getTitle());

            $author = $blog->getAuthor();
            $this->assertEquals(101, $author->getId());
            $this->assertEquals("jim", $author->getUsername());

            $posts = $blog->getPosts();
            $this->assertEquals(2, count($posts));

            $post = $blog->getPosts()[0];
            $this->assertEquals(1, $post->getId());
            $this->assertEquals("Corn nuts", $post->getSubject());

            $comments = $post->getComments();
            $this->assertEquals(2, count($comments));

            $tags = $post->getTags();
            $this->assertEquals(3, count($tags));

            //results are returned unsorted, so there is no guarantee, that it will be in particular order
            //$comment = $comments[0];
            //$this->assertEquals(1, $comment->getId());

            $this->assertEquals(DraftPost::class, get_class($blog->getPosts()[0]));
            $this->assertEquals(Post::class, get_class($blog->getPosts()[1]));
        } finally {
            $session->close();
        }
    }

    public function testShouldNestedSelectBlogWithPostsAndAuthorUsingJoin(): void
    {
        try {
            $session = self::$sqlSessionFactory->openSession();
            $blog = $session->selectOne("Tests\Domain\Blog\Mappers\NestedBlogMapper.selectBlogJoinedWithPostsAndAuthor", 1);
            $this->assertEquals("Jim Business", $blog->getTitle());

            $author = $blog->getAuthor();
            $this->assertEquals(101, $author->getId());
            $this->assertEquals("jim", $author->getUsername());

            $posts = $blog->getPosts();
            $this->assertEquals(2, count($posts));

            $post = $blog->getPosts()[0];
            $this->assertEquals(1, $post->getId());
            $this->assertEquals("Corn nuts", $post->getSubject());

            $comments = $post->getComments();
            $this->assertEquals(2, count($comments));

            $tags = $post->getTags();
            $this->assertEquals(3, count($tags));

            // results are returned unsorted, so there is no guarantee, that it will be returned in particular order
            //$comment = $comments[0];
            //$this->assertEquals(1, $comment->getId());

            $this->assertEquals(DraftPost::class, get_class($blog->getPosts()[0]));
            $this->assertEquals(Post::class, get_class($blog->getPosts()[1]));
        } finally {
            $session->close();
        }
    }

    public function testShouldCacheAllAuthors(): void
    {
        try {
            $session = self::$sqlSessionFactory->openSession();
            $authors1 = $session->selectList("Tests\Builder\CachedAuthorMapper.selectAllAuthors");
            //first = System.identityHashCode(authors);
            $session->commit(); // commit should not be required for read/only activity.
        } finally {
            $session->close();
        }
        try {
            $session = self::$sqlSessionFactory->openSession();
            $authors2 = $session->selectList("Tests\Builder\CachedAuthorMapper.selectAllAuthors");
        } finally {
            $session->close();
        }
        $this->assertSame($authors1, $authors2);
    }

    public function testShouldSelectAuthorsUsingMapperClass(): void
    {
        try {
            $session = self::$sqlSessionFactory->openSession();
            $mapper = $session->getMapper(AuthorMapper::class);
            $authors = $mapper->selectAllAuthors();
            $this->assertEquals(2, count($authors));
        } finally {
            $session->close();
        }
    }

    public function testShouldExecuteSelectOneAuthorUsingMapperClass(): void
    {
        try {
            $session = self::$sqlSessionFactory->openSession();
            $mapper = $session->getMapper(AuthorMapper::class);
            $author = $mapper->selectAuthor(101);
            $this->assertEquals(101, $author->getId());
        } finally {
            $session->close();
        }
    }

    public function testShouldExecuteSelectOneAuthorUsingMapperClassWithResultHandler(): void
    {
        try {
            $session = self::$sqlSessionFactory->openSession();
            $handler = new DefaultResultHandler();
            $mapper = $session->getMapper(AuthorMapper::class);
            $mapper->selectAuthorWithHandler(101, $handler);
            $author = $handler->getResultList()[0];
            $this->assertEquals(101, $author->getId());
        } finally {
            $session->close();
        }
    }

    public function testShouldSelectAuthorsUsingMapperClassWithResultHandler(): void
    {
        try {
            $session = self::$sqlSessionFactory->openSession();
            $handler = new DefaultResultHandler();
            $mapper = $session->getMapper(AuthorMapper::class);
            $mapper->selectAllAuthorsWithHandler($handler);
            $this->assertEquals(2, count($handler->getResultList()));
        } finally {
            $session->close();
        }
    }

    public function testShouldInsertAuthorUsingMapperClass(): void
    {
        try {
            $session = self::$sqlSessionFactory->openSession();
            $mapper = $session->getMapper(AuthorMapper::class);
            $expected = new Author(500, "cbegin", "******", "cbegin@somewhere.com", "Something...", null);
            $mapper->insertAuthor($expected);
            $actual = $mapper->selectAuthor(500);
            $this->assertNotNull($actual);
            $this->assertEquals($expected->getId(), $actual->getId());
            $this->assertEquals($expected->getUsername(), $actual->getUsername());
            $this->assertEquals($expected->getPassword(), $actual->getPassword());
            $this->assertEquals($expected->getEmail(), $actual->getEmail());
            $this->assertEquals($expected->getBio(), $actual->getBio());
        } finally {
            $session->close();
        }
    }

    public function testShouldDeleteAuthorUsingMapperClass(): void
    {
        try {
            $session = self::$sqlSessionFactory->openSession();
            $mapper = $session->getMapper(AuthorMapper::class);
            $count = $mapper->deleteAuthor(101);
            $this->assertEquals(1, $count);
            $this->assertNull($mapper->selectAuthor(101));
        } finally {
            $session->close();
        }
    }

    public function testShouldUpdateAuthorUsingMapperClass(): void
    {
        try {
            $session = self::$sqlSessionFactory->openSession();
            $mapper = $session->getMapper(AuthorMapper::class);
            $expected = $mapper->selectAuthor(101);
            $expected->setUsername("NewUsername");
            $count = $mapper->updateAuthor($expected);
            $this->assertEquals(1, $count);
            $actual = $mapper->selectAuthor(101);
            $this->assertEquals($expected->getUsername(), $actual->getUsername());
        } finally {
            $session->close();
        }
    }

    public function testShouldSelectAllPostsUsingMapperClass(): void
    {
        try {
            $session = self::$sqlSessionFactory->openSession();
            $mapper = $session->getMapper(BlogMapper::class);
            $posts = $mapper->selectAllPosts();
            $this->assertEquals(5, count($posts));
        } finally {
            $session->close();
        }
    }

    public function testShouldLimitResultsUsingMapperClass(): void
    {
        try {
            $session = self::$sqlSessionFactory->openSession();
            $mapper = $session->getMapper(BlogMapper::class);
            $posts = $mapper->selectAllPosts(new RowBounds(0, 2), null);
            $this->assertEquals(2, count($posts));
            $this->assertEquals(1, $posts[0]["id"]);
            $this->assertEquals(2, $posts[1]["id"]);
        } finally {
            $session->close();
        }
    }

    public function testShouldHandleZeroParameters(): void
    {
        try {
            $session = self::$sqlSessionFactory->openSession();
            $resultHandler = new TestResultHandler();
            $session->select("Tests\Domain\Blog\Mappers\BlogMapper.selectAllPosts", null, null, $resultHandler);
            $this->assertEquals(5, $resultHandler->count);
        } finally {
            $session->close();
        }
    }

    public function testStopResultHandler(): void
    {
        try {
            $session = self::$sqlSessionFactory->openSession();
            $resultHandler = new TestResultStopHandler();
            $session->select("Tests\Domain\Blog\Mappers\BlogMapper.selectAllPosts", null, null, $resultHandler);
            $this->assertEquals(2, $resultHandler->count);
        } finally {
            $session->close();
        }
    }

    public function testShouldOffsetAndLimitResultsUsingMapperClass(): void
    {
        try {
            $session = self::$sqlSessionFactory->openSession();
            $mapper = $session->getMapper(BlogMapper::class);
            $posts = $mapper->selectAllPosts(new RowBounds(2, 3));
            $this->assertEquals(3, count($posts));
            $this->assertEquals(3, $posts[0]["id"]);
            $this->assertEquals(4, $posts[1]["id"]);
            $this->assertEquals(5, $posts[2]["id"]);
        } finally {
            $session->close();
        }
    }

    public function testShouldFindPostsAllPostsWithDynamicSql(): void
    {
        try {
            $session = self::$sqlSessionFactory->openSession();
            $posts = $session->selectList("Tests\Domain\Blog\Mappers\PostMapper.findPost");
            $this->assertEquals(5, count($posts));
        } finally {
            $session->close();
        }
    }

    public function testShouldFindPostByIDWithDynamicSql(): void
    {
        try {
            $session = self::$sqlSessionFactory->openSession();
            $posts = $session->selectList("Tests\Domain\Blog\Mappers\PostMapper.findPost", ["id" => 1]);
            $this->assertEquals(1, count($posts));
        } finally {
            $session->close();
        }
    }

    public function testShouldFindPostsInSetOfIDsWithDynamicSql(): void
    {
        try {
            $session = self::$sqlSessionFactory->openSession();
            $posts = $session->selectList("Tests\Domain\Blog\Mappers\PostMapper.findPost", ["ids" => [1, 2, 3]]);
            $this->assertEquals(3, count($posts));
        } finally {
            $session->close();
        }
    }

    public function testShouldFindPostsWithBlogIdUsingDynamicSql(): void
    {
        try {
            $session = self::$sqlSessionFactory->openSession();
            $posts = $session->selectList("Tests\Domain\Blog\Mappers\PostMapper.findPost", ["blog_id" => 1]);
            $this->assertEquals(2, count($posts));
        } finally {
            $session->close();
        }
    }

    public function testShouldFindPostsWithAuthorIdUsingDynamicSql(): void
    {
        try {
            $session = self::$sqlSessionFactory->openSession();
            $posts = $session->selectList("Tests\Domain\Blog\Mappers\PostMapper.findPost", ["author_id" => 101]);
            $this->assertEquals(3, count($posts));
        } finally {
            $session->close();
        }
    }

    public function testShouldFindPostsWithAuthorAndBlogIdUsingDynamicSql(): void
    {
        try {
            $session = self::$sqlSessionFactory->openSession();
            $posts = $session->selectList("Tests\Domain\Blog\Mappers\PostMapper.findPost", ["ids" => [1, 2, 3], "blog_id" => 1]);
            $this->assertEquals(2, count($posts));
        } finally {
            $session->close();
        }
    }

    public function testShouldFindPostsInList(): void
    {
        try {
            $session = self::$sqlSessionFactory->openSession();
            $posts = $session->selectList("Tests\Domain\Blog\Mappers\PostMapper.selectPostIn", [1, 3, 5]);
            $this->assertEquals(3, count($posts));
        } finally {
            $session->close();
        }
    }

    public function testShouldFindOddPostsInList(): void
    {
        try {
            $session = self::$sqlSessionFactory->openSession();
            $posts = $session->selectList("Tests\Domain\Blog\Mappers\PostMapper.selectOddPostsIn", [0, 1, 2, 3, 4]);
            // we're getting odd indexes, not odd values, 0 is not odd
            $this->assertEquals(2, count($posts));
            $this->assertEquals(1, $posts[0]->getId());
            $this->assertEquals(3, $posts[1]->getId());
        } finally {
            $session->close();
        }
    }

    public function testShouldSelectOddPostsInKeysList(): void
    {
        try {
            $session = self::$sqlSessionFactory->openSession();
            $posts = $session->selectList("Tests\Domain\Blog\Mappers\PostMapper.selectOddPostsInKeysList", ["keys" => [0, 1, 2, 3, 4]]);
            // we're getting odd indexes, not odd values, 0 is not odd
            $this->assertEquals(2, count($posts));
            $this->assertEquals(1, $posts[0]->getId());
            $this->assertEquals(3, $posts[1]->getId());
        } finally {
            $session->close();
        }
    }
}
