<?php

namespace Tests\Scripting;

use PHPUnit\Framework\TestCase;
use MyBatis\Scripting\XmlTags\ExpressionEvaluator;
use Tests\Domain\Blog\Author;

class ExpressionEvaluatorTest extends TestCase
{
    private static $evaluator;

    public function setUp(): void
    {
        if (self::$evaluator == null) {
            self::$evaluator = new ExpressionEvaluator();
        }
    }

    public function testShouldCompareStringsReturnTrue(): void
    {
        $value = self::$evaluator->evaluateBoolean("username == 'cbegin'", new Author(1, "cbegin", "******", "cbegin@apache.org", "N/A", "news"));
        $this->assertTrue($value);
    }

    public function testShouldCompareStringsReturnFalse(): void
    {
        $value = self::$evaluator->evaluateBoolean("username == 'norm'", new Author(1, "cbegin", "******", "cbegin@apache.org", "N/A", "news"));
        $this->assertFalse($value);
    }

    public function testShouldReturnTrueIfNotNull(): void
    {
        $value = self::$evaluator->evaluateBoolean("username", new Author(1, "cbegin", "******", "cbegin@apache.org", "N/A", "news"));
        $this->assertTrue($value);
    }

    public function testShouldReturnFalseIfNull(): void
    {
        $value = self::$evaluator->evaluateBoolean("password", new Author(1, "cbegin", null, "cbegin@apache.org", "N/A", "news"));
        $this->assertFalse($value);
    }

    public function testShouldReturnTrueIfNotZero(): void
    {
        $value = self::$evaluator->evaluateBoolean("id", new Author(1, "cbegin", null, "cbegin@apache.org", "N/A", "news"));
        $this->assertTrue($value);
    }

    public function testShouldReturnFalseIfZero(): void
    {
        $value = self::$evaluator->evaluateBoolean("id", new Author(0, "cbegin", null, "cbegin@apache.org", "N/A", "news"));
        $this->assertFalse($value);
    }

    public function testShouldReturnFalseIfZeroWithScale(): void
    {
        $bean = new class () {
            public $d = 0.0;
        };
        $this->assertFalse(self::$evaluator->evaluateBoolean("d", $bean));
    }

    public function testShouldIterateOverIterable(): void
    {
        $parameterObject = [ "array" => ["1", "2", "3" ] ];
        $iterable = self::$evaluator->evaluateIterable("array", $parameterObject);
        $i = 1;
        foreach ($iterable as $o) {
            $this->assertEquals(strval($i++), $o);
        }
    }
}
