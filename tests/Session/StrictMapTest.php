<?php

namespace Tests\Session;

use PHPUnit\Framework\TestCase;
use MyBatis\Session\{
    Ambiguity,
    StrictMap
};

class StrictMapTest/* extends TestCase*/
{
    public function putException(): void
    {
        $sm = new StrictMap("Test");
        $sm->put(0, 1);
        $sm->put(1, 2);
        $this->expectException(\Exception::class);
        $sm->put(0, 3);
    }

    public function ambiguity(): void
    {
        $sm = new StrictMap("Test");
        $sm->put('hello', 'done');
        $sm->put('world.hello', 'collision');
        $this->assertTrue($sm->get("hello") instanceof Ambiguity);
    }

    public function getException(): void
    {
        $sm = new StrictMap("Test");
        $this->expectException(\Exception::class);
        $sm->get("hello");
    }
}
