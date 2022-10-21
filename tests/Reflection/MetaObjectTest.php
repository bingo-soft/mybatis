<?php

namespace Tests\Reflection;

use PHPUnit\Framework\TestCase;
use Util\Reflection\{
    MetaClass,
    MetaObject,
    SystemMetaObject
};
use Tests\Domain\Blog\Author;
use Tests\Domain\Misc\RichType;

class MetaObjectTest extends TestCase
{
    public function testOriginalObject(): void
    {
        $obj = new TestClass();
        $ref = new MetaObject($obj);
        $this->assertSame($obj, $ref->getOriginalObject());
        $this->assertTrue($ref->getOriginalObject() instanceof \Serializable);
    }

    public function testShouldGetAndSetField(): void
    {
        $rich = new RichType();
        $meta = SystemMetaObject::forObject($rich);
        $meta->setValue("richField", "foo");
        $this->assertEquals("foo", $meta->getValue("richField"));
    }

    public function testShouldGetAndSetNestedField(): void
    {
        $rich = new RichType();
        $meta = SystemMetaObject::forObject($rich);
        $meta->setValue("richType.richField", "foo");
        $this->assertEquals("foo", $meta->getValue("richType.richField"));
    }

    public function testShouldGetAndSetProperty(): void
    {
        $rich = new RichType();
        $meta = SystemMetaObject::forObject($rich);
        $meta->setValue("richProperty", "foo");
        $this->assertEquals("foo", $meta->getValue("richProperty"));
    }

    public function testShouldGetAndSetNestedProperty(): void
    {
        $rich = new RichType();
        $meta = SystemMetaObject::forObject($rich);
        $meta->setValue("richType.richField", "foo");
        $this->assertEquals("foo", $meta->getValue("richType.richField"));
    }

    public function testShouldGetAndSetMapPair(): void
    {
        $rich = new RichType();
        $meta = SystemMetaObject::forObject($rich);
        $meta->setValue("richMap.key", "foo");
        $this->assertEquals("foo", $meta->getValue("richMap.key"));
    }

    public function testShouldGetAndSetMapPairUsingArraySyntax(): void
    {
        $rich = new RichType();
        $meta = SystemMetaObject::forObject($rich);
        $meta->setValue("richMap[key]", "foo");
        $this->assertEquals("foo", $meta->getValue("richMap[key]"));
    }

    public function testShouldGetAndSetNestedMapPair(): void
    {
        $rich = new RichType();
        $meta = SystemMetaObject::forObject($rich);
        $meta->setValue("richType.richMap.key", "foo");
        $this->assertEquals("foo", $meta->getValue("richType.richMap.key"));
    }

    public function testShouldGetAndSetNestedMapPairUsingArraySyntax(): void
    {
        $rich = new RichType();
        $meta = SystemMetaObject::forObject($rich);
        $meta->setValue("richType.richMap[key]", "foo");
        $this->assertEquals("foo", $meta->getValue("richType.richMap[key]"));
    }

    public function testShouldGetAndSetListItem(): void
    {
        $rich = new RichType();
        $meta = SystemMetaObject::forObject($rich);
        $meta->setValue("richList[0]", "foo");
        $this->assertEquals("foo", $meta->getValue("richList[0]"));
    }

    public function testShouldGetAndSetNestedListItem(): void
    {
        $rich = new RichType();
        $meta = SystemMetaObject::forObject($rich);
        $meta->setValue("richType.richList[0]", "foo");
        $this->assertEquals("foo", $meta->getValue("richType.richList[0]"));
    }

    public function testShouldGetReadablePropertyNames(): void
    {
        $rich = new RichType();
        $meta = SystemMetaObject::forObject($rich);
        $readables = $meta->getGetterNames();
        $this->assertCount(5, $readables);
        foreach ($readables as $readable) {
            $this->assertTrue($meta->hasGetter($readable));
            $this->assertTrue($meta->hasGetter("richType." . $readable));
        }
        $this->assertTrue($meta->hasGetter("richType"));
    }

    public function testShouldGetWriteablePropertyNames(): void
    {
        $rich = new RichType();
        $meta = SystemMetaObject::forObject($rich);
        $writeables = $meta->getSetterNames();
        $this->assertCount(5, $writeables);
        foreach ($writeables as $writeable) {
            $this->assertTrue($meta->hasSetter($writeable));
            $this->assertTrue($meta->hasSetter("richType." . $writeable));
        }
        $this->assertTrue($meta->hasSetter("richType"));
    }

    public function testShouldSetPropertyOfNullNestedProperty(): void
    {
        $richWithNull = SystemMetaObject::forObject(new RichType());
        $richWithNull->setValue("richType.richProperty", "foo");
        $this->assertEquals("foo", $richWithNull->getValue("richType.richProperty"));
    }

    public function testShouldSetPropertyOfNullNestedPropertyWithNull(): void
    {
        $richWithNull = SystemMetaObject::forObject(new RichType());
        $richWithNull->setValue("richType.richProperty", null);
        $this->assertTrue($richWithNull->getValue("richType.richProperty") === null);
    }

    public function testShouldGetPropertyOfNullNestedProperty(): void
    {
        $richWithNull = SystemMetaObject::forObject(new RichType());
        $this->assertTrue($richWithNull->getValue("richType.richProperty") === null);
    }

    public function testShouldVerifyHasReadablePropertiesReturnedByGetReadablePropertyNames(): void
    {
        $object = SystemMetaObject::forObject(new Author());
        foreach ($object->getGetterNames() as $readable) {
            $this->assertTrue($object->hasGetter($readable));
        }
    }

    public function testShouldVerifyHasWriteablePropertiesReturnedByGetWriteablePropertyNames(): void
    {
        $object = SystemMetaObject::forObject(new Author());
        foreach ($object->getSetterNames() as $writeable) {
            $this->assertTrue($object->hasSetter($writeable));
        }
    }

    public function testShouldSetAndGetProperties(): void
    {
        $object = SystemMetaObject::forObject(new Author());
        $object->setValue("email", "test");
        $this->assertEquals("test", $object->getValue("email"));
    }

    public function testShouldVerifyPropertyTypes(): void
    {
        $object = SystemMetaObject::forObject(new Author());
        $this->assertCount(6, $object->getSetterNames());
        $this->assertEquals("int", $object->getGetterType("id"));
        $this->assertEquals("string", $object->getGetterType("username"));
        $this->assertEquals("string", $object->getGetterType("password"));
        $this->assertEquals("string", $object->getGetterType("email"));
        $this->assertEquals("string", $object->getGetterType("bio"));
        $this->assertEquals("string", $object->getGetterType("favouriteSection"));
    }

    public function testShouldDemonstrateDeeplyNestedMapProperties(): void
    {
        $map = [];
        $metaMap = SystemMetaObject::forObject($map);

        $this->assertTrue($metaMap->hasSetter("id"));
        $this->assertTrue($metaMap->hasSetter("name.first"));
        $this->assertTrue($metaMap->hasSetter("address.street"));

        $this->assertFalse($metaMap->hasGetter("id"));
        $this->assertFalse($metaMap->hasGetter("name.first"));
        $this->assertFalse($metaMap->hasGetter("address.street"));

        $metaMap->setValue("id", "100");
        $metaMap->setValue("name.first", "Clinton");
        $metaMap->setValue("name.last", "Begin");
        $metaMap->setValue("address.street", "1 Some Street");
        $metaMap->setValue("address.city", "This City");
        $metaMap->setValue("address.province", "A Province");
        $metaMap->setValue("address.postal_code", "1A3 4B6");

        $this->assertTrue($metaMap->hasGetter("id"));
        $this->assertTrue($metaMap->hasGetter("name.first"));
        $this->assertTrue($metaMap->hasGetter("address.street"));

        $this->assertCount(3, $metaMap->getGetterNames());
        $this->assertCount(3, $metaMap->getSetterNames());

        $name = $metaMap->getValue("name");
        $address = $metaMap->getValue("address");

        $this->assertEquals("Clinton", $name["first"]);
        $this->assertEquals("1 Some Street", $address["street"]);
    }

    public function testShouldDemonstrateNullValueInMap(): void
    {
        $map = [];
        $metaMap = SystemMetaObject::forObject($map);
        $this->assertFalse($metaMap->hasGetter("phone.home"));

        $metaMap->setValue("phone", null);
        $this->assertTrue($metaMap->hasGetter("phone"));
        // hasGetter returns true if the parent exists and is null.
        $this->assertTrue($metaMap->hasGetter("phone.home"));
        $this->assertTrue($metaMap->hasGetter("phone.home.ext"));
        $this->assertTrue($metaMap->getValue("phone") === null);
        $this->assertTrue($metaMap->getValue("phone.home") === null);
        $this->assertTrue($metaMap->getValue("phone.home.ext") === null);

        $metaMap->setValue("phone.office", "789");
        $this->assertFalse($metaMap->hasGetter("phone.home"));
        $this->assertFalse($metaMap->hasGetter("phone.home.ext"));
        $this->assertEquals("789", $metaMap->getValue("phone.office"));
        $this->assertFalse($metaMap->getValue("phone") === null);
        $this->assertTrue($metaMap->getValue("phone.home") === null);
    }

    public function testShouldMethodHasGetterReturnTrueWhenListElementSet(): void
    {
        $param1 = [];
        $param1[] = "firstParam";
        $param1[] = 222;
        $param1[] = new \DateTime();

        $parametersEmulation = [];
        $parametersEmulation["param1"] = $param1;
        $parametersEmulation["filterParams"] = $param1;

        $meta = SystemMetaObject::forObject($parametersEmulation);

        $this->assertEquals($param1[0], $meta->getValue("filterParams[0]"));
        $this->assertEquals($param1[1], $meta->getValue("filterParams[1]"));
        $this->assertEquals($param1[2], $meta->getValue("filterParams[2]"));

        $this->assertTrue($meta->hasGetter("filterParams[0]"));
        $this->assertTrue($meta->hasGetter("filterParams[1]"));
        $this->assertTrue($meta->hasGetter("filterParams[2]"));
    }
}
