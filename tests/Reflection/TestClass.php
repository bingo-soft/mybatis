<?php

namespace Tests\Reflection;

class TestClass extends ParentClass implements \Serializable
{
    public $foo;
    private $boo;

    public function serialize()
    {
        return json_encode([
            'foo' => $this->foo,
            'boo' => $this->boo
        ]);
    }

    public function unserialize($data)
    {
        $json = json_decode($data);
        $this->foo = $json->foo;
        $this->boo = $json->boo;
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
