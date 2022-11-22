<?php

namespace Tests\Builder;

use MyBatis\Plugin\{
    Interceptor,
    Intercepts,
    Invocation,
    Plugin
};

#[Intercepts([])]
class ExamplePlugin extends Interceptor
{
    private $properties = [];

    public function intercept(Invocation $invocation)
    {
        return $this->invocation->proceed();
    }

    public function plugin($target)
    {
        return Plugin::wrap($target, $this);
    }

    public function setProperties(array $properties): void
    {
        $this->properties = $properties;
    }

    public function getProperties(): array
    {
        return $this->properties;
    }
}
