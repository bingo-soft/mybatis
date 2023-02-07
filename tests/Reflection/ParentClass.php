<?php

namespace Tests\Reflection;

class ParentClass
{
    public $mamba = 25;

    protected int $goo;

    public function setMamba(int $val): void
    {
        $this->mamba = $val;
    }

    protected function setGoo(int $goo): void
    {
        $this->goo = $goo;
    }

    protected function getGoo(): int
    {
        return $this->goo;
    }
}
