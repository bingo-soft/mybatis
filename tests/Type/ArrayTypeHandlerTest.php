<?php

namespace Tests\Type;

use Doctrine\DBAL\Types\Types;
use MyBatis\Type\{
    ArrayTypeHandler,
    TypeException
};

class ArrayTypeHandlerTest extends BaseTypeHandlerTest
{
    private static $TYPE_HANDLER;
    private $mockArray;

    public function setUp(): void
    {
        parent::setUp();
        $this->mockArray = [];
        if (self::$TYPE_HANDLER === null) {
            self::$TYPE_HANDLER = new ArrayTypeHandler();
        }
    }

    public function testShouldSetParameter(): void
    {
        $this->ps->expects($this->once())->method('bindValue')->withConsecutive([1, $this->mockArray]);
        self::$TYPE_HANDLER->setParameter($this->ps, 1, $this->mockArray, null);
    }

    public function testShouldSetStringArrayParameter(): void
    {
        $this->mockArray = ['Hello World'];
        $this->ps->expects($this->once())->method('bindValue')->withConsecutive([1, ['Hello World']]);
        self::$TYPE_HANDLER->setParameter($this->ps, 1, $this->mockArray, null);
    }

    public function testShouldSetNullParameter(): void
    {
        $this->ps->expects($this->once())->method('bindValue')->withConsecutive([1, null]);
        self::$TYPE_HANDLER->setParameter($this->ps, 1, null, Types::ARRAY);
    }

    public function testShouldFailForNonArrayParameter(): void
    {
        $this->expectException(TypeException::class);
        self::$TYPE_HANDLER->setParameter($this->ps, 1, "unsupported parameter type", Types::ARRAY);
    }

    public function testShouldGetResultFromResultSetByName(): void
    {
        $this->mockArray = ['column' => 'value'];

        $this->rs->method('fetchAssociative')
             ->willReturn($this->mockArray);

        $this->assertEquals('value', self::$TYPE_HANDLER->getResult($this->rs, "column"));
    }

    public function testShouldGetResultFromResultSetByPosition(): void
    {
        $this->mockArray = ["a", "b"];
        $this->rs->method('fetchNumeric')
             ->willReturn($this->mockArray);
        $this->assertEquals('b', self::$TYPE_HANDLER->getResult($this->rs, 1));
    }
}
