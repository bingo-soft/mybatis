<?php

namespace Tests\Binding;

use MyBatis\Binding\MapperMethodInvokerInterface;
use MyBatis\Cursor\CursorInterface;
use MyBatis\Exception\PersistenceException;
use MyBatis\Executor\Result\DefaultResultHandler;
use MyBatis\Mapping\Environment;
use MyBatis\Session\{
    Configuration,
    RowBounds,
    SqlSessionInterface,
    SqlSessionFactoryInterface,
    SqlSessionFactoryBuilder
};
use MyBatis\Binding\ParamMap;
use MyBatis\Transaction\TransactionFactoryInterface;
use MyBatis\Transaction\Dbal\DbalTransactionFactory;
use PHPUnit\Framework\TestCase;
use Tests\BaseDataTest;
use Tests\Domain\Blog\{
    Author,
    Blog,
    DraftPost,
    Post,
    Section
};

class BindingTest extends TestCase
{
    private static $sqlSessionFactory;

    public static function setUpBeforeClass(): void
    {
        $dataSource = BaseDataTest::createBlogDataSource();
        $transactionFactory = new DbalTransactionFactory();
        $environment = new Environment("Production", $transactionFactory, $dataSource);
        $configuration = new Configuration($environment);
        $configuration->setLazyLoadingEnabled(true);
        $configuration->setUseActualParamName(false); // to test legacy style reference (#{0} #{1})
        $configuration->getTypeAliasRegistry()->registerAlias(Blog::class);
        $configuration->getTypeAliasRegistry()->registerAlias(Post::class);
        $configuration->getTypeAliasRegistry()->registerAlias(Author::class);
        $configuration->addMapper(BoundBlogMapper::class);
        $configuration->addMapper(BoundAuthorMapper::class);
        self::$sqlSessionFactory = (new SqlSessionFactoryBuilder())->build($configuration);
    }

    public function testShouldFindPostsInList(): void
    {
        try {
            $session = self::$sqlSessionFactory->openSession();
            $mapper = $session->getMapper(BoundAuthorMapper::class);
            $posts = $mapper->findPostsInList([1, 3, 5]);
            $this->assertCount(3, $posts);
            $session->rollback();
        } finally {
            $session->close();
        }
    }

    public function testShouldFindThreeSpecificPosts(): void
    {
        try {
            $session = self::$sqlSessionFactory->openSession();
            $mapper = $session->getMapper(BoundAuthorMapper::class);
            $posts = $mapper->findThreeSpecificPosts(1, new RowBounds(1, 1), 3, 5);
            $this->assertCount(1, $posts);
            $this->assertEquals(3, $posts[0]->getId());
            $session->rollback();
        } finally {
            $session->close();
        }
    }

    public function testShouldInsertAuthorWithSelectKey(): void
    {
        try {
            $session = self::$sqlSessionFactory->openSession();
            $mapper = $session->getMapper(BoundAuthorMapper::class);
            $author = new Author(-1, "cbegin", "******", "cbegin@nowhere.com", "N/A", Section::NEWS);
            $rows = $mapper->insertAuthor($author);
            $this->assertEquals(1, $rows);
            $session->rollback();
        } finally {
            $session->close();
        }
    }

    public function testVerifyErrorMessageFromSelectKey(): void
    {
        try {
            $session = self::$sqlSessionFactory->openSession();
            try {
                $mapper = $session->getMapper(BoundAuthorMapper::class);
                $author = new Author(-1, "cbegin", "******", "cbegin@nowhere.com", "N/A", Section::NEWS);
                $this->expectException(\Exception::class);
                $mapper->insertAuthorInvalidSelectKey($author);
            } finally {
                $session->rollback();
            }
        } finally {
            $session->close();
        }
    }

    public function testVerifyErrorMessageFromInsertAfterSelectKey(): void
    {
        try {
            $session = self::$sqlSessionFactory->openSession();
            try {
                $mapper = $session->getMapper(BoundAuthorMapper::class);
                $author = new Author(-1, "cbegin", "******", "cbegin@nowhere.com", "N/A", Section::NEWS);
                $this->expectException(\Exception::class);
                $mapper->insertAuthorInvalidInsert($author);
            } finally {
                $session->rollback();
            }
        } finally {
            $session->close();
        }
    }

    public function testShouldInsertAuthorWithSelectKeyAndDynamicParams(): void
    {
        try {
            $session = self::$sqlSessionFactory->openSession();
            $mapper = $session->getMapper(BoundAuthorMapper::class);
            $author = new Author(-1, "cbegin", "******", "cbegin@nowhere.com", "N/A", Section::NEWS);
            $rows = $mapper->insertAuthorDynamic($author);
            $this->assertEquals(1, $rows);
            $this->assertNotEquals(-1, $author->getId()); // id must be autogenerated
            $author2 = $mapper->selectAuthor($author->getId());
            $this->assertNotNull($author2);
            $this->assertEquals($author->getEmail(), $author2->getEmail());
            $session->rollback();
        } finally {
            $session->close();
        }
    }

    public function testShouldSelectOneAuthor(): void
    {
        try {
            $session = self::$sqlSessionFactory->openSession();
            $mapper = $session->getMapper(BoundAuthorMapper::class);
            $author = $mapper->selectAuthor(101);
            $this->assertEquals(101, $author->getId());
            $this->assertEquals("jim", $author->getUsername());
            $this->assertEquals("********", $author->getPassword());
            $this->assertEquals("jim@ibatis.apache.org", $author->getEmail());
            $this->assertEquals("", $author->getBio());
        } finally {
            $session->close();
        }
    }

    public function testShouldSelectOneAuthorFromCache(): void
    {
        $author1 = $this->selectOneAuthor();
        $author2 = $this->selectOneAuthor();
        $this->assertSame($author1, $author2);
    }

    private function selectOneAuthor(): Author
    {
        try {
            $session = self::$sqlSessionFactory->openSession();
            $mapper = $session->getMapper(BoundAuthorMapper::class);
            return $mapper->selectAuthor(101);
        } finally {
            $session->close();
        }
    }

    public function testShouldSelectOneAuthorByConstructor(): void
    {
        try {
            $session = self::$sqlSessionFactory->openSession();
            $mapper = $session->getMapper(BoundAuthorMapper::class);
            $author = $mapper->selectAuthorConstructor(101);
            $this->assertEquals(101, $author->getId());
            $this->assertEquals("jim", $author->getUsername());
            $this->assertEquals("********", $author->getPassword());
            $this->assertEquals("jim@ibatis.apache.org", $author->getEmail());
            $this->assertEquals("", $author->getBio());
        } finally {
            $session->close();
        }
    }

    public function testShouldMapPropertiesUsingRepeatableAnnotation(): void
    {
        try {
            $session = self::$sqlSessionFactory->openSession();
            $mapper = $session->getMapper(BoundAuthorMapper::class);
            $author = new Author(-1, "cbegin", "******", "cbegin@nowhere.com", "N/A", Section::NEWS);
            $mapper->insertAuthor($author);
            $author2 = $mapper->selectAuthorMapToPropertiesUsingRepeatable($author->getId());
            $this->assertNotNull($author2);
            $this->assertEquals($author->getId(), $author2->getId());
            $this->assertEquals($author->getUsername(), $author2->getUsername());
            $this->assertEquals($author->getPassword(), $author2->getPassword());
            $this->assertEquals($author->getBio(), $author2->getBio());
            $this->assertEquals($author->getEmail(), $author2->getEmail());
            $session->rollback();
        } finally {
            $session->close();
        }
    }

    public function testShouldMapConstructorUsingRepeatableAnnotation(): void
    {
        try {
            $session = self::$sqlSessionFactory->openSession();
            $mapper = $session->getMapper(BoundAuthorMapper::class);
            $author = new Author(-1, "cbegin", "******", "cbegin@nowhere.com", "N/A", Section::NEWS);
            $mapper->insertAuthor($author);
            $author2 = $mapper->selectAuthorMapToConstructorUsingRepeatable($author->getId());
            $this->assertNotNull($author2);
            $this->assertEquals($author->getId(), $author2->getId());
            $this->assertEquals($author->getUsername(), $author2->getUsername());
            $this->assertEquals($author->getPassword(), $author2->getPassword());
            $this->assertEquals($author->getBio(), $author2->getBio());
            $this->assertEquals($author->getEmail(), $author2->getEmail());
            $this->assertEquals($author->getFavouriteSection(), $author2->getFavouriteSection());
            $session->rollback();
        } finally {
            $session->close();
        }
    }

    public function testShouldMapUsingSingleRepeatableAnnotation(): void
    {
        try {
            $session = self::$sqlSessionFactory->openSession();
            $mapper = $session->getMapper(BoundAuthorMapper::class);
            $author = new Author(-1, "cbegin", "******", "cbegin@nowhere.com", "N/A", Section::NEWS);
            $mapper->insertAuthor($author);
            $author2 = $mapper->selectAuthorUsingSingleRepeatable($author->getId());
            $this->assertNotNull($author2);
            $this->assertEquals($author->getId(), $author2->getId());
            $this->assertEquals($author->getUsername(), $author2->getUsername());
            $this->assertNull($author2->getPassword());
            $this->assertNull($author2->getBio());
            $this->assertNull($author2->getEmail());
            $this->assertNull($author2->getFavouriteSection());
            $session->rollback();
        } finally {
            $session->close();
        }
    }

    public function testShouldMapWhenSpecifyBothArgAndConstructorArgs(): void
    {
        try {
            $session = self::$sqlSessionFactory->openSession();
            $mapper = $session->getMapper(BoundAuthorMapper::class);
            $author = new Author(-1, "cbegin", "******", "cbegin@nowhere.com", "N/A", Section::NEWS);
            $mapper->insertAuthor($author);
            $author2 = $mapper->selectAuthorUsingBothArgAndConstructorArgs($author->getId());
            $this->assertNotNull($author2);
            $this->assertEquals($author->getId(), $author2->getId());
            $this->assertEquals($author->getUsername(), $author2->getUsername());
            $this->assertEquals($author->getPassword(), $author2->getPassword());
            $this->assertEquals($author->getBio(), $author2->getBio());
            $this->assertEquals($author->getEmail(), $author2->getEmail());
            $this->assertEquals($author->getFavouriteSection(), $author2->getFavouriteSection());
            $session->rollback();
        } finally {
            $session->close();
        }
    }

    public function testShouldMapWhenSpecifyBothResultAndResults(): void
    {
        try {
            $session = self::$sqlSessionFactory->openSession();
            $mapper = $session->getMapper(BoundAuthorMapper::class);
            $author = new Author(-1, "cbegin", "******", "cbegin@nowhere.com", "N/A", Section::NEWS);
            $mapper->insertAuthor($author);
            $author2 = $mapper->selectAuthorUsingBothResultAndResults($author->getId());
            $this->assertNotNull($author2);
            $this->assertEquals($author->getId(), $author2->getId());
            $this->assertEquals($author->getUsername(), $author2->getUsername());
            $this->assertNull($author2->getPassword());
            $this->assertNull($author2->getBio());
            $this->assertNull($author2->getEmail());
            $this->assertNull($author2->getFavouriteSection());
            $session->rollback();
        } finally {
            $session->close();
        }
    }

    public function testShouldSelectBlogWithPostsUsingSubSelect(): void
    {
        try {
            $session = self::$sqlSessionFactory->openSession();
            $mapper = $session->getMapper(BoundBlogMapper::class);
            $b = $mapper->selectBlogWithPostsUsingSubSelect(1);
            $this->assertEquals(1, $b->getId());
            $this->assertNotNull($b->getAuthor());
            $this->assertEquals(101, $b->getAuthor()->getId());
            $this->assertEquals("jim", $b->getAuthor()->getUsername());
            $this->assertEquals("********", $b->getAuthor()->getPassword());
            $this->assertEquals(2, count($b->getPosts()));
        } finally {
            $session->close();
        }
    }

    public function testShouldSelectRandom(): void
    {
        try {
            $session = self::$sqlSessionFactory->openSession();
            $mapper = $session->getMapper(BoundBlogMapper::class);
            $x = $mapper->selectRandom();
            $this->assertNotNull($x);
        } finally {
            $session->close();
        }
    }

    public function testShouldExecuteBoundSelectListOfBlogsStatement(): void
    {
        try {
            $session = self::$sqlSessionFactory->openSession();
            $mapper = $session->getMapper(BoundBlogMapper::class);
            $blogs = $mapper->selectBlogs();
            $this->assertEquals(2, count($blogs));
        } finally {
            $session->close();
        }
    }

    public function testShouldExecuteBoundSelectMapOfBlogsById(): void
    {
        try {
            $session = self::$sqlSessionFactory->openSession();
            $mapper = $session->getMapper(BoundBlogMapper::class);
            $blogs = $mapper->selectBlogsAsMapById();
            $this->assertEquals(2, count($blogs));
            foreach ($blogs as $key => $value) {
                $this->assertEquals($key, $value->getId());
            }
        } finally {
            $session->close();
        }
    }

    public function testShouldExecuteMultipleBoundSelectOfBlogsByIdInWithProvidedResultHandlerBetweenSessions(): void
    {
        $handler = new DefaultResultHandler();
        try {
            $session = self::$sqlSessionFactory->openSession();
            $session->select("selectBlogsAsMapById", null, null, $handler);
        } finally {
            $session->close();
        }

        $moreHandler = new DefaultResultHandler();
        try {
            $session = self::$sqlSessionFactory->openSession();
            $session->select("selectBlogsAsMapById", null, null, $moreHandler);
        } finally {
            $session->close();
        }
        $this->assertEquals(2, count($handler->getResultList()));
        $this->assertEquals(2, count($moreHandler->getResultList()));
    }

    public function testShouldExecuteMultipleBoundSelectOfBlogsByIdInWithProvidedResultHandlerInSameSession(): void
    {
        try {
            $session = self::$sqlSessionFactory->openSession();
            $handler = new DefaultResultHandler();
            $session->select("selectBlogsAsMapById", null, null, $handler);

            $moreHandler = new DefaultResultHandler();
            $session->select("selectBlogsAsMapById", null, null, $moreHandler);

            $this->assertEquals(2, count($handler->getResultList()));
            $this->assertEquals(2, count($moreHandler->getResultList()));
        } finally {
            $session->close();
        }
    }

    public function testShouldExecuteMultipleBoundSelectMapOfBlogsByIdInSameSessionWithoutClearingLocalCache(): void
    {
        try {
            $session = self::$sqlSessionFactory->openSession();
            $mapper = $session->getMapper(BoundBlogMapper::class);
            $blogs = $mapper->selectBlogsAsMapById();
            $moreBlogs = $mapper->selectBlogsAsMapById();
            $this->assertCount(2, $blogs);
            $this->assertCount(2, $moreBlogs);
            foreach ($blogs as $key => $value) {
                $this->assertEquals($key, $value->getId());
            }
            foreach ($moreBlogs as $key => $value) {
                $this->assertEquals($key, $value->getId());
            }
        } finally {
            $session->close();
        }
    }

    public function testShouldExecuteMultipleBoundSelectMapOfBlogsByIdBetweenTwoSessionsWithGlobalCacheEnabled(): void
    {
        $blogs = [];
        try {
            $session = self::$sqlSessionFactory->openSession();
            $mapper = $session->getMapper(BoundBlogMapper::class);
            $blogs = $mapper->selectBlogsAsMapById();
        } finally {
            $session->close();
        }
        try {
            $session = self::$sqlSessionFactory->openSession();
            $mapper = $session->getMapper(BoundBlogMapper::class);
            $moreBlogs = $mapper->selectBlogsAsMapById();
            $this->assertCount(2, $blogs);
            $this->assertCount(2, $moreBlogs);
            foreach ($blogs as $key => $value) {
                $this->assertEquals($key, $value->getId());
            }
            foreach ($moreBlogs as $key => $value) {
                $this->assertEquals($key, $value->getId());
            }
        } finally {
            $session->close();
        }
    }

    public function testShouldSelectListOfBlogsUsingXMLConfig(): void
    {
        try {
            $session = self::$sqlSessionFactory->openSession();
            $mapper = $session->getMapper(BoundBlogMapper::class);
            $blogs = $mapper->selectBlogsFromXML();
            $this->assertEquals(2, count($blogs));
        } finally {
            $session->close();
        }
    }

    public function testShouldExecuteBoundSelectListOfBlogsStatementUsingProvider(): void
    {
        try {
            $session = self::$sqlSessionFactory->openSession();
            $mapper = $session->getMapper(BoundBlogMapper::class);
            $blogs = $mapper->selectBlogsUsingProvider();
            $this->assertEquals(2, count($blogs));
        } finally {
            $session->close();
        }
    }

    public function testShouldExecuteBoundSelectListOfBlogsAsMaps(): void
    {
        try {
            $session = self::$sqlSessionFactory->openSession();
            $mapper = $session->getMapper(BoundBlogMapper::class);
            $blogs = $mapper->selectBlogsAsMaps();
            $this->assertEquals(2, count($blogs));
        } finally {
            $session->close();
        }
    }

    public function testShouldSelectListOfPostsLike(): void
    {
        try {
            $session = self::$sqlSessionFactory->openSession();
            $mapper = $session->getMapper(BoundBlogMapper::class);
            $posts = $mapper->selectPostsLike(new RowBounds(1, 1), "%a%");
            $this->assertEquals(1, count($posts));
        } finally {
            $session->close();
        }
    }

    public function testShouldSelectListOfPostsLikeTwoParameters(): void
    {
        try {
            $session = self::$sqlSessionFactory->openSession();
            $mapper = $session->getMapper(BoundBlogMapper::class);
            $posts = $mapper->selectPostsLikeSubjectAndBody(new RowBounds(1, 1), "%a%", "%a%");
            $this->assertEquals(1, count($posts));
        } finally {
            $session->close();
        }
    }

    public function testShouldExecuteBoundSelectOneBlogStatement(): void
    {
        try {
            $session = self::$sqlSessionFactory->openSession();
            $mapper = $session->getMapper(BoundBlogMapper::class);
            $blog = $mapper->selectBlog(1);
            $this->assertEquals(1, $blog->getId());
            $this->assertEquals("Jim Business", $blog->getTitle());
        } finally {
            $session->close();
        }
    }

    public function testShouldExecuteBoundSelectOneBlogStatementWithConstructor(): void
    {
        try {
            $session = self::$sqlSessionFactory->openSession();
            $mapper = $session->getMapper(BoundBlogMapper::class);
            $blog = $mapper->selectBlogUsingConstructor(1);
            $this->assertEquals(1, $blog->getId());
            $this->assertEquals("Jim Business", $blog->getTitle());
            $this->assertNotNull($blog->getAuthor());
            $posts = $blog->getPosts();
            $this->assertTrue(!empty($posts));
        } finally {
            $session->close();
        }
    }

    public function testShouldExecuteBoundSelectBlogUsingConstructorWithResultMap(): void
    {
        try {
            $session = self::$sqlSessionFactory->openSession();
            $mapper = $session->getMapper(BoundBlogMapper::class);
            $blog = $mapper->selectBlogUsingConstructorWithResultMap(1);
            $this->assertEquals(1, $blog->getId());
            $this->assertEquals("Jim Business", $blog->getTitle());
            $this->assertNotNull($blog->getAuthor());
            $posts = $blog->getPosts();
            $this->assertTrue(!empty($posts));
        } finally {
            $session->close();
        }
    }

    public function testShouldExecuteBoundSelectBlogUsingConstructorWithResultMapAndProperties(): void
    {
        try {
            $session = self::$sqlSessionFactory->openSession();
            $mapper = $session->getMapper(BoundBlogMapper::class);
            $blog = $mapper->selectBlogUsingConstructorWithResultMapAndProperties(1);
            $this->assertEquals(1, $blog->getId());
            $this->assertEquals("Jim Business", $blog->getTitle());
            $this->assertNotNull($blog->getAuthor(), "author should not be null");
            $author = $blog->getAuthor();
            $this->assertEquals(101, $author->getId());
            $this->assertEquals("jim@ibatis.apache.org", $author->getEmail());
            $this->assertEquals("jim", $author->getUsername());
            $this->assertEquals("NEWS", $author->getFavouriteSection());
            $posts = $blog->getPosts();
            $this->assertNotNull($posts, "posts should not be empty");
            $this->assertEquals(2, count($posts));
        } finally {
            $session->close();
        }
    }

    //TODO. disabled
    /*public function testShouldExecuteBoundSelectBlogUsingConstructorWithResultMapCollection(): void
    {
        try {
            $session = self::$sqlSessionFactory->openSession();
            $mapper = $session->getMapper(BoundBlogMapper::class);
            $blog = $mapper->selectBlogUsingConstructorWithResultMapCollection(1);
            $this->assertEquals(1, $blog->getId());
            $this->assertEquals("Jim Business", $blog->getTitle());
            $this->assertNotNull($blog->getAuthor(), "author should not be null");
            $posts = $blog->getPosts();
            $this->assertTrue(!empty($posts), "posts should not be empty");
        } finally {
            $session->close();
        }
    }*/

    public function testShouldExecuteBoundSelectOneBlogStatementWithConstructorUsingXMLConfig(): void
    {
        try {
            $session = self::$sqlSessionFactory->openSession();
            $mapper = $session->getMapper(BoundBlogMapper::class);
            $blog = $mapper->selectBlogByIdUsingConstructor(1);
            $this->assertEquals(1, $blog->getId());
            $this->assertEquals("Jim Business", $blog->getTitle());
            $this->assertNotNull($blog->getAuthor(), "author should not be null");
            $posts = $blog->getPosts();
            $this->assertTrue(!empty($posts), "posts should not be empty");
        } finally {
            $session->close();
        }
    }

    public function testShouldSelectOneBlogAsMap(): void
    {
        try {
            $session = self::$sqlSessionFactory->openSession();
            $mapper = $session->getMapper(BoundBlogMapper::class);
            $blog = $mapper->selectBlogAsMap(["id" => 1]);
            $this->assertEquals(1, $blog["id"]);
            $this->assertEquals("Jim Business", $blog["title"]);
        } finally {
            $session->close();
        }
    }
}
