<?php

namespace Tests\Plugin;

use MyBatis\Plugin\{
    Interceptor,
    Intercepts,
    Invocation,
    Signature
};

#[Intercepts([new Signature(type: CustomArray::class, method: "get", args: [])])]
class AlwaysMapPlugin extends Interceptor
{
    public function intercept(Invocation $invocation)
    {
        return "Always";// . $invocation->proceed();
    }
}
