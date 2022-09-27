<?php

namespace Tests\Io;

use MyBatis\Builder\{
    BuilderException,
    ParameterExpression
};
use PHPUnit\Framework\TestCase;

class ParameterExpressionTest extends TestCase
{
    public function testSimpleProperty(): void
    {
        $result = new ParameterExpression("id");
        $this->assertCount(1, $result);
        $this->assertEquals("id", $result['property']);
        $this->assertEquals("id", $result->get('property'));
    }

    public function testPropertyWithSpacesInside(): void
    {
        $result = new ParameterExpression(" with spaces ");
        $this->assertCount(1, $result);
        $this->assertEquals("with spaces", $result->get('property'));
    }

    public function testSimplePropertyWithOldStyleSqlType(): void
    {
        $result = new ParameterExpression("id:VARCHAR");
        $this->assertCount(2, $result);
        $this->assertEquals("id", $result->get('property'));
        $this->assertEquals("VARCHAR", $result->get('sqlType'));
    }

    public function testOldStyleSqlTypeWithExtraWhitespaces(): void
    {
        $result = new ParameterExpression(" id :  VARCHAR");
        $this->assertCount(2, $result);
        $this->assertEquals("id", $result->get('property'));
        $this->assertEquals("VARCHAR", $result->get('sqlType'));
    }

    public function testExpressionWithOldStyleJdbcType(): void
    {
        $result = new ParameterExpression("(id.toString()):VARCHAR");
        $this->assertCount(2, $result);
        $this->assertEquals("id.toString()", $result->get('expression'));
        $this->assertEquals("VARCHAR", $result->get('sqlType'));
    }

    public function testSimplePropertyWithOneAttribute(): void
    {
        $result = new ParameterExpression("id,name=value");
        $this->assertCount(2, $result);
        $this->assertEquals("id", $result->get('property'));
        $this->assertEquals("value", $result->get('name'));
    }

    public function testExpressionWithOneAttribute(): void
    {
        $result = new ParameterExpression("(id.toString()),name=value");
        $this->assertCount(2, $result);
        $this->assertEquals("id.toString()", $result->get('expression'));
        $this->assertEquals("value", $result->get('name'));
    }

    public function testSimplePropertyWithManyAttributes(): void
    {
        $result = new ParameterExpression("id, attr1=val1, attr2=val2, attr3=val3");
        $this->assertCount(4, $result);
        $this->assertEquals("id", $result->get('property'));
        $this->assertEquals("val1", $result->get('attr1'));
        $this->assertEquals("val2", $result->get('attr2'));
        $this->assertEquals("val3", $result->get('attr3'));
    }

    public function testExpressionWithManyAttributes(): void
    {
        $result = new ParameterExpression("(id.toString()), attr1=val1, attr2=val2, attr3=val3");
        $this->assertCount(4, $result);
        $this->assertEquals("id.toString()", $result->get('expression'));
        $this->assertEquals("val1", $result->get('attr1'));
        $this->assertEquals("val2", $result->get('attr2'));
        $this->assertEquals("val3", $result->get('attr3'));
    }

    public function testSimplePropertyWithOldStyleSqlTypeAndAttributes(): void
    {
        $result = new ParameterExpression("id:VARCHAR, attr1=val1, attr2=val2");
        $this->assertCount(4, $result);
        $this->assertEquals("id", $result->get('property'));
        $this->assertEquals("VARCHAR", $result->get('sqlType'));
        $this->assertEquals("val1", $result->get('attr1'));
        $this->assertEquals("val2", $result->get('attr2'));
    }

    public function testSimplePropertyWithSpaceAndManyAttributes(): void
    {
        $result = new ParameterExpression("user name, attr1=val1, attr2=val2, attr3=val3");
        $this->assertCount(4, $result);
        $this->assertEquals("user name", $result->get('property'));
        $this->assertEquals("val1", $result->get('attr1'));
        $this->assertEquals("val2", $result->get('attr2'));
        $this->assertEquals("val3", $result->get('attr3'));
    }

    public function testShouldIgnoreLeadingAndTrailingSpaces(): void
    {
        $result = new ParameterExpression(" id , sqlType =  VARCHAR,  attr1 = val1 ,  attr2 = val2 ");
        $this->assertCount(4, $result);
        $this->assertEquals("id", $result->get('property'));
        $this->assertEquals("VARCHAR", $result->get('sqlType'));
        $this->assertEquals("val1", $result->get('attr1'));
        $this->assertEquals("val2", $result->get('attr2'));
    }

    public function testInvalidOldSqlTypeFormat(): void
    {
        $this->expectException(BuilderException::class);
        new ParameterExpression("id:");
    }

    public function testInvalidJdbcTypeOptUsingExpression(): void
    {
        $this->expectException(BuilderException::class);
        new ParameterExpression("(expression)+");
    }
}
