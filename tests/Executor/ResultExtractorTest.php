<?php

namespace Tests\Executor;

use MyBatis\Executor\{
    ExecutorException,
    ResultExtractor
};
use MyBatis\Session\Configuration;
use PHPUnit\Framework\TestCase;

class ResultExtractorTest extends TestCase
{
    private $resultExtractor;

    public function setUp(): void
    {
        $this->resultExtractor = new ResultExtractor($this->createMock(Configuration::class));
    }

    public function testShouldExtractNullForNullTargetType(): void
    {
        $result = $this->resultExtractor->extractObjectFromList(null, null);
        $this->assertNull($result);
    }

    public function testShouldExtractList(): void
    {
        $list = [1, 2, 3];
        $result = $this->resultExtractor->extractObjectFromList($list, gettype($list));
        $this->assertTrue(is_array($result));
        $this->assertEquals($list, $result);
    }

    public function testShouldExtractSingleObject(): void
    {
        $list = [ "single object" ];
        $this->assertEquals("single object", $this->resultExtractor->extractObjectFromList($list, "string"));
        $this->assertEquals("single object", $this->resultExtractor->extractObjectFromList($list, null));
        $this->assertEquals("single object", $this->resultExtractor->extractObjectFromList($list, "integer"));
    }

    public function testShouldFailWhenMutipleItemsInList(): void
    {
        $list = ["first object", "second object"];
        $this->expectException(ExecutorException::class);
        $this->resultExtractor->extractObjectFromList($list, "string");
    }
}
