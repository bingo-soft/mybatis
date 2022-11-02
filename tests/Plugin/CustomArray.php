<?php

namespace Tests\Plugin;

class CustomArray extends \ArrayObject
{
    public function get(string $key)
    {
        return $this[$key];
    }

    public function get2(string $key)
    {
        return $this[$key];
    }
}
