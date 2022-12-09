<?php

namespace MyBatis\Plugin;

abstract class Interceptor implements InterceptorInterface
{
    abstract public function intercept(Invocation $invocation);

    public function plugin($target)
    {
        return PluginFactory::wrap($target, $this);
    }

    public function setProperties(array $properties): void
    {
        // NOP
    }
}
