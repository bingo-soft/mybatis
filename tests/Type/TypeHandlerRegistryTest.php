<?php

namespace Tests\Type;

use Doctrine\DBAL\{
    Result,
    Statement
};
use MyBatis\Type\{
    BaseTypeHandler,
    DateTypeHandler,
    IntegerTypeHandler,
    StringTypeHandler,
    TypeHandlerInterface,
    TypeHandlerRegistry,
    UnknownTypeHandler
};
use PHPUnit\Framework\TestCase;
use Tests\Domain\{
    Address,
    MyDate,
    MyDate2
};
use Tests\Domain\Misc\RichType;

class TypeHandlerRegistryTest extends TestCase
{
    private $typeHandlerRegistry;

    public function setUp(): void
    {
        $this->typeHandlerRegistry = new TypeHandlerRegistry();
    }

    public function testShouldRegisterAndRetrieveTypeHandler(): void
    {
        $stringTypeHandler = $this->typeHandlerRegistry->getTypeHandler("string");
        $this->typeHandlerRegistry->register("String", $stringTypeHandler);
        $this->assertEquals($stringTypeHandler, $this->typeHandlerRegistry->getTypeHandler("String"));
        $this->assertTrue($this->typeHandlerRegistry->hasTypeHandler("String"));
        $this->assertFalse($this->typeHandlerRegistry->hasTypeHandler(RichType::class));
        $this->assertTrue($this->typeHandlerRegistry->getUnknownTypeHandler() instanceof UnknownTypeHandler);
    }

    public function testShouldRegisterAndRetrieveComplexTypeHandler(): void
    {
        $fakeHandler = new class () implements TypeHandlerInterface
        {
            public function setParameter(Statement $ps, int $i, $parameter, string $type = null): void
            {
                //do nothing
            }

            public function getResult($rs, $column)
            {
                return null;
            }
        };

        $this->typeHandlerRegistry->register("fake", $fakeHandler);
        $this->assertEquals($fakeHandler, $this->typeHandlerRegistry->getTypeHandler("fake"));
    }

    public function testShouldAutoRegisterAndRetrieveComplexTypeHandler(): void
    {
        $fakeHandler = new class () extends BaseTypeHandler
        {
            public function setNonNullParameter(Statement $ps, /*string|int*/$i, $parameter, string $type = null): void
            {
                //
            }

            public function getNullableResult(Result $rs, $column)
            {
                return null;
            }
        };

        $this->typeHandlerRegistry->register("foo", $fakeHandler);

        $this->assertEquals($fakeHandler, $this->typeHandlerRegistry->getTypeHandler("foo"));
    }

    public function testShouldBindHandlersToWrappersAndPrimitivesIndividually(): void
    {
        $this->typeHandlerRegistry->register("Integer", DateTypeHandler::class);
        $this->assertTrue($this->typeHandlerRegistry->getTypeHandler("int") instanceof IntegerTypeHandler);
        $this->typeHandlerRegistry->register("Integer", IntegerTypeHandler::class);
        $this->typeHandlerRegistry->register("int", DateTypeHandler::class);
        $this->assertTrue($this->typeHandlerRegistry->getTypeHandler("Integer") instanceof IntegerTypeHandler);
    }

    public function testShouldReturnHandlerForSuperclassIfRegistered(): void
    {
        $this->assertEquals(DateTypeHandler::class, get_class($this->typeHandlerRegistry->getTypeHandler(MyDate::class)));
    }

    public function testShouldReturnHandlerForSuperSuperclassIfRegistered(): void
    {
        $this->assertEquals(DateTypeHandler::class, get_class($this->typeHandlerRegistry->getTypeHandler(MyDate2::class)));
    }

    public function testShouldRegisterReplaceNullMap(): void
    {
        $this->assertFalse($this->typeHandlerRegistry->hasTypeHandler(Address::class));
        $this->typeHandlerRegistry->register(Address::class, StringTypeHandler::class);
        $this->assertTrue($this->typeHandlerRegistry->hasTypeHandler(Address::class));
    }
}
