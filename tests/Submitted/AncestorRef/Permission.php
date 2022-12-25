<?php

namespace Tests\Submitted\AncestorRef;

class Permission
{
    public $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}
