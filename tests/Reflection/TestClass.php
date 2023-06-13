<?php

namespace Tests\Reflection;

class TestClass extends ParentClass
{
    public $foo;
    private $boo;

    public function __serialize(): array
    {
        return [
            'foo' => $this->foo,
            'boo' => $this->boo
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->foo = $data['foo'];
        $this->boo = $data['boo'];
    }

    public function doo(int $goo, string $zoo): int
    {
        return 0;
    }

    public function setFoo(int $val): void
    {
        $this->foo = $val;
    }
}
