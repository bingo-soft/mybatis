<?php

namespace MyBatis\Reflection\Invoker;

class AmbiguousMethodInvoker extends MethodInvoker
{
    private $exceptionMessage;

    public function __construct(\ReflectionMethod $method, string $exceptionMessage)
    {
        parent::__construct($method);
        $this->exceptionMessage = $exceptionMessage;
    }

    public function invoke($target, array $args = [])
    {
        throw new \ReflectionException($this->exceptionMessage);
    }
}
