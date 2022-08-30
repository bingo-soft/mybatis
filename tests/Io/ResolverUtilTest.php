<?php

namespace Tests\Io;

use MyBatis\Io\{
    IsA,
    ResolverUtil
};
use PHPUnit\Framework\TestCase;

class ResolverUtilTest extends TestCase
{
    public function testIsA(): void
    {
        $child = new SubchildClass();
        $test = new IsA(ParentClass::class);
        $this->assertTrue($test->matches($child));
        $this->assertEquals('is assignable to Tests\Io\ParentClass', $test);
    }

    public function testFind(): void
    {
        $resolverUtil = new ResolverUtil();
        $resolverUtil->find(new IsA(ChildClass::class), "tests\Io");
        $this->assertCount(2, $resolverUtil->getClasses());
        $this->assertEquals('Tests\Io\ChildClass', $resolverUtil->getClasses()[0]);
        $this->assertEquals('Tests\Io\SubchildClass', $resolverUtil->getClasses()[1]);

        $resolverUtil = new ResolverUtil();
        $resolverUtil->find(new IsA('object'), "tests\Io");
        $this->assertCount(4, $resolverUtil->getClasses());
    }
}
