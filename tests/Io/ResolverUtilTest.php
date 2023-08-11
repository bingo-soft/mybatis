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
        $this->assertContains('Tests\Io\ChildClass', $resolverUtil->getClasses());
        $this->assertContains('Tests\Io\SubchildClass', $resolverUtil->getClasses());

        $resolverUtil = new ResolverUtil();
        $resolverUtil->find(new IsA('object'), "tests\Io");
        $this->assertCount(5, $resolverUtil->getClasses());
    }
}
