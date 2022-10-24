<?php

namespace MyBatis\Plugin;

class Invocation
{
    private $target;
    private $method;
    private $args = [];

    public function __construct($target, \ReflectionMethod $method, array $args)
    {
        $this->target = $target;
        $this->method = $method;
        $this->args = $args;
    }

    public function getTarget()
    {
        return $this->target;
    }

    public function getMethod(): \ReflectionMethod
    {
        return $this->method;
    }

    public function getArgs(): array
    {
        return $this->args;
    }

    public function proceed()
    {
        return $this->method->invoke($this->target, ...$this->args);
    }
}
