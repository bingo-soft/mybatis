<?php

namespace Tests\Type;

use Doctrine\DBAL\Types\Types;
use MyBatis\Type\{
    JsonTypeHandler,
    TypeException
};

class JsonTypeHandlerTest extends BaseTypeHandlerTest
{
    private static $TYPE_HANDLER;
    private $mockJson;

    public function setUp(): void
    {
        parent::setUp();
        $this->mockJson = [];
        if (self::$TYPE_HANDLER === null) {
            self::$TYPE_HANDLER = new JsonTypeHandler();
        }
    }

    private function testShouldSetParameter(): void
    {
        $this->ps->expects($this->once())->method('bindValue')->withConsecutive([1, $this->mockJson]);
        self::$TYPE_HANDLER->setParameter($this->ps, 1, $this->mockJson, null);
    }

    private function testShouldSetStringArrayParameter(): void
    {
        $this->mockJson = ['Hello World'];
        $this->ps->expects($this->once())->method('bindValue')->withConsecutive([1, ['Hello World']]);
        self::$TYPE_HANDLER->setParameter($this->ps, 1, $this->mockJson, null);
    }

    private function testShouldSetNullParameter(): void
    {
        $this->ps->expects($this->once())->method('bindValue')->withConsecutive([1, null]);
        self::$TYPE_HANDLER->setParameter($this->ps, 1, null, Types::JSON);
    }

    /*public function testShouldFailForNonArrayParameter(): void
    {
        $this->expectException(TypeException::class);
        self::$TYPE_HANDLER->setParameter($this->ps, 1, "unsupported parameter type", Types::JSON);
    }*/

    public function testShouldGetResultFromResultSetByName(): void
    {
        $this->mockJson = ['column' => 'value'];

        $this->rs->method('fetchAssociative')
             ->willReturn($this->mockJson);

        $this->assertEquals('value', self::$TYPE_HANDLER->getResult($this->rs, "column"));
    }

    public function testShouldGetResultFromResultSetByPosition(): void
    {
        $this->mockJson = ["a", "b"];
        $this->rs->method('fetchNumeric')
             ->willReturn($this->mockJson);
        $this->assertEquals('b', self::$TYPE_HANDLER->getResult($this->rs, 1));
    }
}
