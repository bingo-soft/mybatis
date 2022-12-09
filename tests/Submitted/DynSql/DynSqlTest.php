<?php

namespace Tests\Submitted\DynSql;

use Doctrine\DBAL\{
    Result,
    Statement
};
use MyBatis\Io\Resources;
use MyBatis\Session\{
    Configuration,
    SqlSessionFactoryInterface,
    SqlSessionFactoryBuilder
};
use MyBatis\Type\TypeHandlerInterface;
use PHPUnit\Framework\TestCase;
use Tests\BaseDataTest;

class DynSqlTest extends TestCase
{
    private static $sqlSessionFactory;

    public static function setUpBeforeClass(): void
    {
        $stream = Resources::getResourceAsStream("tests/Resources/Submitted/DynSql/MapperConfig.xml");
        self::$sqlSessionFactory = (new SqlSessionFactoryBuilder())->build($stream);

        BaseDataTest::runScript(
            self::$sqlSessionFactory->getConfiguration()->getEnvironment()->getDataSource(),
            "tests/Resources/Submitted/DynSql/CreateDB.sql"
        );
    }

    public function testSelect(): void
    {
        try {
            $sqlSession = self::$sqlSessionFactory->openSession();
            $ids = [1, 3, 5];
            $parameter = new Parameter();
            $parameter->setEnabled(true);
            $parameter->setSchema("ibtest");
            $parameter->setIds($ids);

            $answer = $sqlSession->selectList("Tests\Submitted\DynSql.select", $parameter);
            $this->assertEquals(3, count($answer));
        } finally {
            $sqlSession->close();
        }
    }

    public function testSelectSimple(): void
    {
        try {
            $sqlSession = self::$sqlSessionFactory->openSession();
            $ids = [1, 3, 5];
            $parameter = new Parameter();
            $parameter->setEnabled(true);
            $parameter->setSchema("ibtest");
            $parameter->setIds($ids);

            $answer = $sqlSession->selectList("Tests\Submitted\DynSql.select_simple", $parameter);
            $this->assertEquals(3, count($answer));
        } finally {
            $sqlSession->close();
        }
    }

    public function testSelectLike(): void
    {
        try {
            $sqlSession = self::$sqlSessionFactory->openSession();

            $answer = $sqlSession->selectList("Tests\Submitted\DynSql.selectLike", 'Ba');
            $this->assertEquals(2, count($answer));
            $this->assertEquals(4, $answer[0]["id"]);
            $this->assertEquals(6, $answer[1]["id"]);
        } finally {
            $sqlSession->close();
        }
    }

    public function testNumerics(): void
    {
        try {
            $sqlSession = self::$sqlSessionFactory->openSession();
            $answer = $sqlSession->selectList("Tests\Submitted\DynSql.selectNumerics");

            $this->assertEquals(1, count($answer));

            $row = $answer[0];
            $this->assertEquals(1, $row->getId());
            $this->assertEquals(2, $row->getTinynumber());
            $this->assertEquals(3, $row->getSmallnumber());
            $this->assertEquals(4, $row->getLonginteger());
            $this->assertEquals(5, $row->getBiginteger());
            $this->assertEquals(6.0, $row->getNumericnumber());
            $this->assertEquals(7.0, $row->getDecimalnumber());
            $this->assertEquals(8.0, $row->getRealnumber());
            $this->assertEquals(9.0, $row->getFloatnumber());
            $this->assertEquals(10.0, $row->getDoublenumber());
        } finally {
            $sqlSession->close();
        }
    }

    public function testOgnlStaticMethodCall(): void
    {
        try {
            $sqlSession = self::$sqlSessionFactory->openSession();
            $answer = $sqlSession->selectList("Tests\Submitted\DynSql.ognlStaticMethodCall", "Rock 'n Roll");
            $this->assertEquals(1, count($answer));
            $this->assertEquals(7, $answer[0]["id"]);
        } finally {
            $sqlSession->close();
        }
    }

    public function testBindNull(): void
    {
        try {
            $sqlSession = self::$sqlSessionFactory->openSession();
            $mapper = $sqlSession->getMapper(DynSqlMapper::class);
            $description = $mapper->selectDescription(null);
            $this->assertEquals("Pebbles", $description);
        } finally {
            $sqlSession->close();
        }
    }

    public function testValueObjectWithoutParamAnnotation(): void
    {
        try {
            $sqlSession = self::$sqlSessionFactory->openSession();
            $mapper = $sqlSession->getMapper(DynSqlMapper::class);
            $descriptions = $mapper->selectDescriptionById(3);
            $this->assertEquals(1, count($descriptions));
            $this->assertEquals("Pebbles", $descriptions[0]);

            $desc = $mapper->selectDescriptionById(null);
            $this->assertEquals(7, count($mapper->selectDescriptionById(null)));
        } finally {
            $sqlSession->close();
        }
    }

    public function testNonValueObjectWithoutParamAnnotation(): void
    {
        try {
            $sqlSession = self::$sqlSessionFactory->openSession();
            $mapper = $sqlSession->getMapper(DynSqlMapper::class);
            $conditions = new Conditions();
            $conditions->setId(3);
            $descriptions = $mapper->selectDescriptionByConditions($conditions);
            $this->assertEquals(1, count($descriptions));
            $this->assertEquals("Pebbles", $descriptions[0]);
            $this->assertEquals(7, count($mapper->selectDescriptionByConditions(null)));
            $this->assertEquals(7, count($mapper->selectDescriptionByConditions(new Conditions())));
        } finally {
            $sqlSession->close();
        }
    }

    public function testNonValueObjectWithoutParamAnnotation2(): void
    {
        try {
            $sqlSession = self::$sqlSessionFactory->openSession();
            $mapper = $sqlSession->getMapper(DynSqlMapper::class);
            $conditions = new Conditions();
            $conditions->setId(3);
            $this->assertEquals(7, count($mapper->selectDescriptionByConditions2(null)));
            $this->expectException(\Exception::class);
            $mapper->selectDescriptionByConditions2($conditions);
        } finally {
            $sqlSession->close();
        }
    }

    public function testNonValueObjectWithoutParamAnnotation3(): void
    {
        try {
            $sqlSession = self::$sqlSessionFactory->openSession();
            $mapper = $sqlSession->getMapper(DynSqlMapper::class);
            $conditions = new Conditions();
            $conditions->setId(3);
            $this->assertEquals(7, count($mapper->selectDescriptionByConditions3(null)));
            $this->expectException(\Exception::class);
            $mapper->selectDescriptionByConditions3($conditions);
        } finally {
            $sqlSession->close();
        }
    }
}
