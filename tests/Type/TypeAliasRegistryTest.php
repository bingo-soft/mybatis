<?php

namespace Tests\Type;

use MyBatis\Type\{
    TypeAliasRegistry,
    TypeException
};
use PHPUnit\Framework\TestCase;

class TypeAliasRegistryTest extends TestCase
{
    public function testShouldRegisterAndResolveTypeAlias(): void
    {
        $typeAliasRegistry = new TypeAliasRegistry();

        $typeAliasRegistry->registerAlias("rich", "Tests\Domain\Misc\RichType");

        $this->assertEquals("Tests\Domain\Misc\RichType", $typeAliasRegistry->resolveAlias("rich"));
    }

    public function testShouldBeAbleToRegisterSameAliasWithSameTypeAgain(): void
    {
        $typeAliasRegistry = new TypeAliasRegistry();
        $typeAliasRegistry->registerAlias("String", "string");
        $typeAliasRegistry->registerAlias("string", "string");

        $this->assertEquals($typeAliasRegistry->resolveAlias("String"), $typeAliasRegistry->resolveAlias("string"));
    }

    public function testShouldNotBeAbleToRegisterSameAliasWithDifferentType(): void
    {
        $typeAliasRegistry = new TypeAliasRegistry();
        $this->expectException(TypeException::class);
        $typeAliasRegistry->registerAlias("string", "bigdecimal");
    }

    public function testShouldBeAbleToRegisterAliasWithNullType(): void
    {
        $typeAliasRegistry = new TypeAliasRegistry();
        $typeAliasRegistry->registerAlias("foo", null);
        $this->assertNull($typeAliasRegistry->resolveAlias("foo"));
    }

    public function testShouldBeAbleToRegisterNewTypeIfRegisteredTypeIsNull(): void
    {
        $typeAliasRegistry = new TypeAliasRegistry();
        $typeAliasRegistry->registerAlias("foo", null);
        $typeAliasRegistry->registerAlias("foo", "string");
        $this->assertEquals("string", $typeAliasRegistry->resolveAlias("foo"));
    }

    public function testShouldFetchCharType(): void
    {
        $typeAliasRegistry = new TypeAliasRegistry();
        $this->assertEquals("string", $typeAliasRegistry->resolveAlias("char"));
        $this->assertEquals("array", $typeAliasRegistry->resolveAlias("char[]"));
        $this->assertEquals("array", $typeAliasRegistry->resolveAlias("_char[]"));
    }
}
