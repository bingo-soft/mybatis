<?php

namespace Tests\Plugin;

class Simple
{
    private $boo = 123;

    public function update(string $class1, string $class2): void
    {
        echo $class1 . ' ' . $class2;
    }
}
