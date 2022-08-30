<?php

namespace Tests\Reflection;

class ParentClass
{
    public $mamba = 25;

    public function setMamba(int $val): void
    {
        $this->mamba = $val;
    }
}
