<?php

namespace MyBatis\Plugin;

use Util\Proxy\MethodHandlerInterface;

class Plugin implements MethodHandlerInterface
{
    private $target;
    private $interceptor;
    private $signatureMap = [];

    public function __construct($target, Interceptor $interceptor, array $signatureMap)
    {
        $this->target = $target;
        $this->interceptor = $interceptor;
        $this->signatureMap = $signatureMap;
    }

    public function invoke($proxy, \ReflectionMethod $thisMethod, \ReflectionMethod $proceed, array $args)
    {
        $cls = $proceed->getDeclaringClass()->name;
        $methods = [];
        if (array_key_exists($cls, $this->signatureMap)) {
            $methods = $this->signatureMap[$cls];
        }
        if (!empty($methods) && in_array($proceed, $methods)) {
            return $this->interceptor->intercept(new Invocation($this->target, $proceed, $args));
        }
        return $proceed->invoke($this->target, ...$args);
    }
}
