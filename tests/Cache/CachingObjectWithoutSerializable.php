<?php

namespace Tests\Cache;

class CachingObjectWithoutSerializable
{
    private $x;

    public function __construct(int $x)
    {
        $this->x = $x;
    }
}
