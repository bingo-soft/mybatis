<?php

namespace Tests\Reflection;

use PHPUnit\Framework\TestCase;
use Util\Reflection\MetaClass;
use Tests\Domain\Misc\RichType;
use Tests\Domain\Misc\Generics\GenericConcrete;

class MetaClassTest extends TestCase
{
    public function testShouldTestDataTypeOfGenericMethod(): void
    {
        $meta = new MetaClass(GenericConcrete::class);
        $this->assertEquals("int", $meta->getGetterType("id"));
        $this->assertNull($meta->getSetterType("id"));
    }

    public function testShouldThrowReflectionExceptionGetGetterType()
    {
        $this->expectException(\ReflectionException::class);
        $meta = new MetaClass(RichType::class);
        $type = $meta->getGetterType("aString");
        $this->assertNull($type);
    }

    public function testShouldCheckGetterExistance(): void
    {
        $meta = new MetaClass(RichType::class);
        $this->assertTrue($meta->hasGetter("richField"));
        $this->assertTrue($meta->hasGetter("richProperty"));
        $this->assertTrue($meta->hasGetter("richList"));
        $this->assertTrue($meta->hasGetter("richMap"));
        $this->assertTrue($meta->hasGetter("richList[0]"));

        $this->assertTrue($meta->hasGetter("richType"));
        $this->assertTrue($meta->hasGetter("richType.richField"));
        $this->assertTrue($meta->hasGetter("richType.richProperty"));
        $this->assertTrue($meta->hasGetter("richType.richList"));
        $this->assertTrue($meta->hasGetter("richType.richMap"));
        $this->assertTrue($meta->hasGetter("richType.richList[0]"));

        $this->assertEquals("richType.richProperty", $meta->findProperty("richType.richProperty", false));

        $this->assertFalse($meta->hasGetter("[0]"));
    }

    public function testShouldCheckSetterExistance(): void
    {
        $meta = new MetaClass(RichType::class);
        $this->assertTrue($meta->hasSetter("richField"));
        $this->assertTrue($meta->hasSetter("richProperty"));
        $this->assertTrue($meta->hasSetter("richList"));
        $this->assertTrue($meta->hasSetter("richMap"));
        $this->assertTrue($meta->hasSetter("richList[0]"));

        $this->assertTrue($meta->hasSetter("richType"));
        $this->assertTrue($meta->hasSetter("richType.richField"));
        $this->assertTrue($meta->hasSetter("richType.richProperty"));
        $this->assertTrue($meta->hasSetter("richType.richList"));
        $this->assertTrue($meta->hasSetter("richType.richMap"));
        $this->assertTrue($meta->hasSetter("richType.richList[0]"));

        $this->assertFalse($meta->hasSetter("[0]"));
    }

    public function testShouldCheckTypeForEachGetter(): void
    {
        $meta = new MetaClass(RichType::class);
        $this->assertEquals("string", $meta->getGetterType("richField"));
        $this->assertEquals("string", $meta->getGetterType("richProperty"));
        $this->assertEquals("array", $meta->getGetterType("richList"));
        $this->assertEquals("array", $meta->getGetterType("richMap"));
        $this->assertEquals("array", $meta->getGetterType("richList[0]"));

        $this->assertEquals(RichType::class, $meta->getGetterType("richType"));
        $this->assertEquals("string", $meta->getGetterType("richType.richField"));
        $this->assertEquals("string", $meta->getGetterType("richType.richProperty"));
        $this->assertEquals("array", $meta->getGetterType("richType.richList"));
        $this->assertEquals("array", $meta->getGetterType("richType.richMap"));
        $this->assertEquals("array", $meta->getGetterType("richType.richList[0]"));
    }

    public function testShouldCheckTypeForEachSetter(): void
    {
        $meta = new MetaClass(RichType::class);
        $this->assertEquals("string", $meta->getSetterType("richField"));
        $this->assertEquals("string", $meta->getSetterType("richProperty"));
        $this->assertEquals("array", $meta->getSetterType("richList"));
        $this->assertEquals("array", $meta->getSetterType("richMap"));
        $this->assertEquals("array", $meta->getSetterType("richList[0]"));

        $this->assertEquals(RichType::class, $meta->getSetterType("richType"));
        $this->assertEquals("string", $meta->getSetterType("richType.richField"));
        $this->assertEquals("string", $meta->getSetterType("richType.richProperty"));
        $this->assertEquals("array", $meta->getSetterType("richType.richList"));
        $this->assertEquals("array", $meta->getSetterType("richType.richMap"));
        $this->assertEquals("array", $meta->getSetterType("richType.richList[0]"));
    }

    public function testShouldCheckGetterAndSetterNames(): void
    {
        $meta = new MetaClass(RichType::class);
        $this->assertCount(5, $meta->getGetterNames());
        $this->assertCount(5, $meta->getSetterNames());
    }

    public function testShouldFindPropertyName(): void
    {
        $meta = new MetaClass(RichType::class);
        $this->assertEquals("richField", $meta->findProperty("RICHfield"));
    }
}
