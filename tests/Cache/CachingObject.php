<?php

namespace Tests\Cache;

class CachingObject
{
    private $x;

    public function __construct(int $x)
    {
        $this->x = $x;
    }

    public function __serialize(): array
    {
        return ['x' => $this->x];
    }

    public function __unserialize(array $obj): void
    {
        $this->x = $obj['x'];
    }
}
