<?php

namespace MyBatis\Reflection\Invoker;

class MethodInvoker implements InvokerInterface
{
    private $type;
    private $method;

    public function __construct(\ReflectionMethod $method)
    {
        $this->method = $method;
        $type = null;
        if (count($method->getParameters()) == 1) {
            $parameter = $method->getParameters()[0];
            $type = $parameter->getType();
        } else {
            $type = $method->getReturnType();
        }
        if ($type !== null && $type instanceof \ReflectionNamedType) {
            $this->type = $type->getName();
        }
    }

    public function invoke($target, array $args = [])
    {
        if ($this->method->isPrivate() || $this->method->isProtected()) {
            $this->method->setAccessible(true);
        }
        return $this->method->invoke($target, ...$args);
    }

    public function getType(): ?string
    {
        return $this->type;
    }
}
