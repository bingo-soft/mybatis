<?php

namespace Tests\Plugin;

class CustomArray extends \ArrayObject
{
    public function get(string $key)
    {
        return $this[$key];
    }
}
