<?php

namespace Tests\Builder\Xml\Dynamic;

use MyBatis\Io\Resources;
use MyBatis\Scripting\XmlTags\{
    ChooseSqlNode,
    DynamicSqlSource,
    ForEachSqlNode,
    IfSqlNode,
    MixedSqlNode,
    SetSqlNode,
    SqlNode,
    TextSqlNode,
    WhereSqlNode
};
use MyBatis\Session\{
    Configuration,
    SqlSessionFactoryInterface,
    SqlSessionFactoryBuilder
};
use PHPUnit\Framework\TestCase;
use Tests\BaseDataTest;

class DynamicSqlSourceTest extends TestCase
{
    public function testShouldDemonstrateSimpleExpectedTextWithNoLoopsOrConditionals(): void
    {
        $expected = "SELECT * FROM BLOG";
        $sqlNode = $this->mixedContents([new TextSqlNode($expected)]);
        $source = $this->createDynamicSqlSource($sqlNode);
        $boundSql = $source->getBoundSql(null);
        $this->assertEquals($expected, $boundSql->getSql());
    }

    public function testShouldDemonstrateMultipartExpectedTextWithNoLoopsOrConditionals(): void
    {
        $expected = "SELECT * FROM BLOG WHERE ID = ?";
        $source = $this->createDynamicSqlSource(
            new TextSqlNode("SELECT * FROM BLOG"),
            new TextSqlNode("WHERE ID = ?")
        );
        $boundSql = $source->getBoundSql(null);
        $this->assertEquals($expected, $boundSql->getSql());
    }

    public function testShouldConditionallyIncludeWhere(): void
    {
        $expected = "SELECT * FROM BLOG WHERE ID = ?";
        $source = $this->createDynamicSqlSource(
            new TextSqlNode("SELECT * FROM BLOG"),
            new IfSqlNode($this->mixedContents([new TextSqlNode("WHERE ID = ?")]), "true")
        );
        $boundSql = $source->getBoundSql(null);
        $this->assertEquals($expected, $boundSql->getSql());
    }

    public function testShouldConditionallyExcludeWhere(): void
    {
        $expected = "SELECT * FROM BLOG";
        $source = $this->createDynamicSqlSource(
            new TextSqlNode("SELECT * FROM BLOG"),
            new IfSqlNode($this->mixedContents([new TextSqlNode("WHERE ID = ?")]), "false")
        );
        $boundSql = $source->getBoundSql(null);
        $this->assertEquals($expected, $boundSql->getSql());
    }

    public function testShouldConditionallyDefault(): void
    {
        $expected = "SELECT * FROM BLOG WHERE CATEGORY = 'DEFAULT'";
        $source = $this->createDynamicSqlSource(
            new TextSqlNode("SELECT * FROM BLOG"),
            new ChooseSqlNode([
              new IfSqlNode($this->mixedContents([new TextSqlNode("WHERE CATEGORY = ?")]), "false"),
              new IfSqlNode($this->mixedContents([new TextSqlNode("WHERE CATEGORY = 'NONE'")]), "false")
            ], $this->mixedContents([new TextSqlNode("WHERE CATEGORY = 'DEFAULT'")]))
        );
        $boundSql = $source->getBoundSql(null);
        $this->assertEquals($expected, $boundSql->getSql());
    }

    public function testShouldConditionallyChooseFirst(): void
    {
        $expected = "SELECT * FROM BLOG WHERE CATEGORY = ?";
        $source = $this->createDynamicSqlSource(
            new TextSqlNode("SELECT * FROM BLOG"),
            new ChooseSqlNode([
              new IfSqlNode($this->mixedContents([new TextSqlNode("WHERE CATEGORY = ?")]), "true"),
              new IfSqlNode($this->mixedContents([new TextSqlNode("WHERE CATEGORY = 'NONE'")]), "false")
            ], $this->mixedContents([new TextSqlNode("WHERE CATEGORY = 'DEFAULT'")]))
        );
        $boundSql = $source->getBoundSql(null);
        $this->assertEquals($expected, $boundSql->getSql());
    }

    public function testShouldConditionallyChooseSecond(): void
    {
        $expected = "SELECT * FROM BLOG WHERE CATEGORY = 'NONE'";
        $source = $this->createDynamicSqlSource(
            new TextSqlNode("SELECT * FROM BLOG"),
            new ChooseSqlNode([
              new IfSqlNode($this->mixedContents([new TextSqlNode("WHERE CATEGORY = ?")]), "false"),
              new IfSqlNode($this->mixedContents([new TextSqlNode("WHERE CATEGORY = 'NONE'")]), "true")
            ], $this->mixedContents([new TextSqlNode("WHERE CATEGORY = 'DEFAULT'")]))
        );
        $boundSql = $source->getBoundSql(null);
        $this->assertEquals($expected, $boundSql->getSql());
    }

    public function testShouldTrimWHEREInsteadOfANDForFirstCondition(): void
    {
        $expected = "SELECT * FROM BLOG WHERE  ID = ?";
        $source = $this->createDynamicSqlSource(
            new TextSqlNode("SELECT * FROM BLOG"),
            new WhereSqlNode(
                new Configuration(),
                $this->mixedContents([
                    new IfSqlNode($this->mixedContents([new TextSqlNode("   and ID = ?  ")]), "true"),
                    new IfSqlNode($this->mixedContents([new TextSqlNode("   or NAME = ?  ")]), "false")
                ])
            )
        );
        $boundSql = $source->getBoundSql(null);
        $this->assertEquals($expected, $boundSql->getSql());
    }

    public function testShouldTrimWHEREANDWithLFForFirstCondition(): void
    {
        $expected = "SELECT * FROM BLOG WHERE \n ID = ?";
        $source = $this->createDynamicSqlSource(
            new TextSqlNode("SELECT * FROM BLOG"),
            new WhereSqlNode(
                new Configuration(),
                $this->mixedContents(
                    [ new IfSqlNode($this->mixedContents([ new TextSqlNode("   and\n ID = ?  ") ]), "true") ]
                )
            )
        );
        $boundSql = $source->getBoundSql(null);
        $this->assertEquals($expected, $boundSql->getSql());
    }

    public function testShouldTrimWHEREANDWithCRLForFirstCondition(): void
    {
        $expected = "SELECT * FROM BLOG WHERE \r\n ID = ?";
        $source = $this->createDynamicSqlSource(
            new TextSqlNode("SELECT * FROM BLOG"),
            new WhereSqlNode(
                new Configuration(),
                $this->mixedContents(
                    [ new IfSqlNode($this->mixedContents([ new TextSqlNode("   and\r\n ID = ?  ") ]), "true") ]
                )
            )
        );
        $boundSql = $source->getBoundSql(null);
        $this->assertEquals($expected, $boundSql->getSql());
    }

    public function testShouldTrimWHEREANDWithTABForFirstCondition(): void
    {
        $expected = "SELECT * FROM BLOG WHERE \t ID = ?";
        $source = $this->createDynamicSqlSource(
            new TextSqlNode("SELECT * FROM BLOG"),
            new WhereSqlNode(
                new Configuration(),
                $this->mixedContents(
                    [ new IfSqlNode($this->mixedContents([ new TextSqlNode("   and\t ID = ?  ") ]), "true") ]
                )
            )
        );
        $boundSql = $source->getBoundSql(null);
        $this->assertEquals($expected, $boundSql->getSql());
    }

    public function testShouldTrimWHEREORWithLFForFirstCondition(): void
    {
        $expected = "SELECT * FROM BLOG WHERE \n ID = ?";
        $source = $this->createDynamicSqlSource(
            new TextSqlNode("SELECT * FROM BLOG"),
            new WhereSqlNode(
                new Configuration(),
                $this->mixedContents(
                    [ new IfSqlNode($this->mixedContents([ new TextSqlNode("   or\n ID = ?  ") ]), "true") ]
                )
            )
        );
        $boundSql = $source->getBoundSql(null);
        $this->assertEquals($expected, $boundSql->getSql());
    }

    public function testShouldTrimWHEREORWithCRLForFirstCondition(): void
    {
        $expected = "SELECT * FROM BLOG WHERE \r\n ID = ?";
        $source = $this->createDynamicSqlSource(
            new TextSqlNode("SELECT * FROM BLOG"),
            new WhereSqlNode(
                new Configuration(),
                $this->mixedContents(
                    [ new IfSqlNode($this->mixedContents([ new TextSqlNode("   or\r\n ID = ?  ") ]), "true") ]
                )
            )
        );
        $boundSql = $source->getBoundSql(null);
        $this->assertEquals($expected, $boundSql->getSql());
    }

    public function testShouldTrimWHEREORWithTABForFirstCondition(): void
    {
        $expected = "SELECT * FROM BLOG WHERE \t ID = ?";
        $source = $this->createDynamicSqlSource(
            new TextSqlNode("SELECT * FROM BLOG"),
            new WhereSqlNode(
                new Configuration(),
                $this->mixedContents(
                    [ new IfSqlNode($this->mixedContents([ new TextSqlNode("   or\t ID = ?  ") ]), "true") ]
                )
            )
        );
        $boundSql = $source->getBoundSql(null);
        $this->assertEquals($expected, $boundSql->getSql());
    }

    public function testShouldTrimWHEREInsteadOfORForSecondCondition(): void
    {
        $expected = "SELECT * FROM BLOG WHERE  NAME = ?";
        $source = $this->createDynamicSqlSource(
            new TextSqlNode("SELECT * FROM BLOG"),
            new WhereSqlNode(
                new Configuration(),
                $this->mixedContents([
                    new IfSqlNode($this->mixedContents([new TextSqlNode("   and ID = ?  ")]), "false"),
                    new IfSqlNode($this->mixedContents([new TextSqlNode("   or NAME = ?  ")]), "true")
                ])
            )
        );
        $boundSql = $source->getBoundSql(null);
        $this->assertEquals($expected, $boundSql->getSql());
    }

    public function testShouldTrimWHEREInsteadOfANDForBothConditions(): void
    {
        $expected = "SELECT * FROM BLOG WHERE  ID = ?   OR NAME = ?";
        $source = $this->createDynamicSqlSource(
            new TextSqlNode("SELECT * FROM BLOG"),
            new WhereSqlNode(
                new Configuration(),
                $this->mixedContents([
                    new IfSqlNode($this->mixedContents([new TextSqlNode("   and ID = ?   ")]), "true"),
                    new IfSqlNode($this->mixedContents([new TextSqlNode("OR NAME = ?  ")]), "true")
                ])
            )
        );
        $boundSql = $source->getBoundSql(null);
        $this->assertEquals($expected, $boundSql->getSql());
    }

    public function testShouldTrimNoWhereClause(): void
    {
        $expected = "SELECT * FROM BLOG";
        $source = $this->createDynamicSqlSource(
            new TextSqlNode("SELECT * FROM BLOG"),
            new WhereSqlNode(
                new Configuration(),
                $this->mixedContents([
                    new IfSqlNode($this->mixedContents([new TextSqlNode("   and ID = ?   ")]), "false"),
                    new IfSqlNode($this->mixedContents([new TextSqlNode("OR NAME = ?  ")]), "false")
                ])
            )
        );
        $boundSql = $source->getBoundSql(null);
        $this->assertEquals($expected, $boundSql->getSql());
    }

    public function testShouldTrimSETInsteadOfCOMMAForBothConditions(): void
    {
        $expected = "UPDATE BLOG SET ID = ?,  NAME = ?";
        $source = $this->createDynamicSqlSource(
            new TextSqlNode("UPDATE BLOG"),
            new SetSqlNode(
                new Configuration(),
                $this->mixedContents([
                    new IfSqlNode($this->mixedContents([new TextSqlNode(" ID = ?, ")]), "true"),
                    new IfSqlNode($this->mixedContents([new TextSqlNode(" NAME = ?, ")]), "true")
                ])
            )
        );
        $boundSql = $source->getBoundSql(null);
        $this->assertEquals($expected, $boundSql->getSql());
    }

    public function testShouldTrimCommaAfterSET(): void
    {
        $expected = "UPDATE BLOG SET  NAME = ?";
        $source = $this->createDynamicSqlSource(
            new TextSqlNode("UPDATE BLOG"),
            new SetSqlNode(
                new Configuration(),
                $this->mixedContents([
                    new IfSqlNode($this->mixedContents([new TextSqlNode("ID = ?")]), "false"),
                    new IfSqlNode($this->mixedContents([new TextSqlNode(", NAME = ?")]), "true")
                ])
            )
        );
        $boundSql = $source->getBoundSql(null);
        $this->assertEquals($expected, $boundSql->getSql());
    }

    public function testShouldTrimNoSetClause(): void
    {
        $expected = "UPDATE BLOG";
        $source = $this->createDynamicSqlSource(
            new TextSqlNode("UPDATE BLOG"),
            new SetSqlNode(
                new Configuration(),
                $this->mixedContents([
                    new IfSqlNode($this->mixedContents([new TextSqlNode("   , ID = ?   ")]), "false"),
                    new IfSqlNode($this->mixedContents([new TextSqlNode(", NAME = ?  ")]), "false")
                ])
            )
        );
        $boundSql = $source->getBoundSql(null);
        $this->assertEquals($expected, $boundSql->getSql());
    }

    public function testShouldMapNullStringsToEmptyStrings(): void
    {
        $expected = 'id=${id}';
        $sqlNode = $this->mixedContents([new TextSqlNode($expected)]);
        $source = new DynamicSqlSource(new Configuration(), $sqlNode);
        $boundsql = $source->getBoundSql(new Bean(null));
        $sql = $boundsql->getSql();
        $this->assertEquals("id=", $sql);
    }

    private function mixedContents(...$contents): MixedSqlNode
    {
        return new MixedSqlNode(...$contents);
    }

    private function createDynamicSqlSource(...$contents): ?DynamicSqlSource
    {
        //No data is needed
        //BaseDataTest::createBlogDataSource();
        $resource = "tests/Resources/Builder/MapperConfig.xml";
        $reader = Resources::getResourceAsStream($resource);
        $sqlMapper = (new SqlSessionFactoryBuilder())->build($reader);
        $configuration = $sqlMapper->getConfiguration();
        $sqlNode = $this->mixedContents($contents);
        return new DynamicSqlSource($configuration, $sqlNode);
    }
}
