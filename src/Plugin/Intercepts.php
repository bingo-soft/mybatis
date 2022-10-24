<?php

namespace MyBatis\Plugin;

#[Attribute]
class Intercepts implements InterceptsInterface
{
    private $sigs = [];

    public function __construct(array $sigs = [])
    {
        $this->sigs = $sigs;
    }

    public function value(): array
    {
        return $this->sigs;
    }
}
