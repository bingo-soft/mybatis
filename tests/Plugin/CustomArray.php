<?php

namespace Tests\Plugin;

class CustomArray extends \ArrayObject implements CustomArrayInterface
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
