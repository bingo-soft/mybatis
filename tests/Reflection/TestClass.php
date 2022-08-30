<?php

namespace Tests\Reflection;

class TestClass extends ParentClass
{
    public $foo;
    private $boo;

    public function doo(int $goo, string $zoo): int
    {
        return 0;
    }

    public function setFoo(int $val): void
    {
        $this->foo = $val;
    }
}
