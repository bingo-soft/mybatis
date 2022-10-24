<?php

namespace Tests\Plugin;

use MyBatis\Plugin\{
    Interceptor,
    Intercepts,
    Invocation,
    Signature
};

#[Intercepts([new Signature(type: Simple::class, method: "update", args: [SomeClass::class, \stdClass::class])])]
class ExamplePlugin extends Interceptor
{
    public function intercept(Invocation $invocation)
    {
        $returnObject = $invocation->proceed();
        return $returnObject;
    }
}
