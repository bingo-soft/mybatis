<?php

namespace Tests\Reflection;

use PHPUnit\Framework\TestCase;
use MyBatis\Reflection\{
    ParamNameResolver,
    ParamNameUtil
};
use MyBatis\Reflection\Property\{
    PropertyCopier,
    PropertyNamer,
    PropertyTokenizer
};
use MyBatis\Session\Configuration;

class ReflectionTest extends TestCase
{
    public function testParamNames(): void
    {
        $ref = new \ReflectionClass(TestClass::class);
        $paramNames = ParamNameUtil::getParamNames($ref->getMethod('doo'));
        $this->assertCount(2, $paramNames);
        $this->assertEquals(['goo', 'zoo'], $paramNames);
    }

    public function testPropertyNamer(): void
    {
        $this->assertEquals("zoo", PropertyNamer::methodToProperty("getZoo"));
        $this->assertEquals("doo", PropertyNamer::methodToProperty("isDoo"));
        $this->assertEquals("foo", PropertyNamer::methodToProperty("setFoo"));

        $this->assertTrue(PropertyNamer::isGetter("isDoo"));
        $this->assertTrue(PropertyNamer::isGetter("getZoo"));
        $this->assertFalse(PropertyNamer::isGetter("setZoo"));
        $this->assertTrue(PropertyNamer::isSetter("setGoo"));
    }

    public function testPropertyCopier(): void
    {
        $one = new TestClass();
        $one->foo = 123;

        $two = new TestClass();
        PropertyCopier::copyObjectProperties(TestClass::class, $one, $two);
        $this->assertEquals(123, $two->foo);
        $this->assertEquals(25, $two->mamba);
    }

    public function testPropertyTokenizer(): void
    {
        $prop = "hello.world.boo";
        $tokenizer = new PropertyTokenizer($prop);
        $this->assertEquals("hello", $tokenizer->getName());
        $tokenizer = $tokenizer->next();
        $this->assertEquals("world", $tokenizer->getName());
        $this->assertTrue($tokenizer->valid());
        $tokenizer = $tokenizer->next();
        $this->assertEquals("boo", $tokenizer->getName());
        $this->assertFalse($tokenizer->valid());
    }

    public function testParamNameResolver(): void
    {
        $conf = new Configuration();
        $ref = new \ReflectionClass(TestClass::class);
        $method = $ref->getMethod('doo');
        $resolver = new ParamNameResolver($conf, $method);
        $this->assertEquals(['goo', 'zoo'], $resolver->getNames());
        $this->assertEquals(['goo' => 100, 'param1' => 100, 'zoo' => 'hello', 'param2' => 'hello'], $resolver->getNamedParams([100, 'hello']));
    }
}
