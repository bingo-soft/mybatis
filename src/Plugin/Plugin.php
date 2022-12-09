<?php

namespace MyBatis\Plugin;

use Util\Proxy\MethodHandlerInterface;

class Plugin implements MethodHandlerInterface
{
    private $target;
    private $interceptor;
    private $signatureMap = [];

    public function __construct($target, Interceptor $interceptor, array $signatureMap = [])
    {
        $this->target = $target;
        $this->interceptor = $interceptor;
        $this->signatureMap = $signatureMap;
    }

    public function invoke($proxy, \ReflectionMethod $thisMethod, \ReflectionMethod $proceed, array $args)
    {
        $methodExists = false;
        if (!empty($this->signatureMap)) {
            foreach ($this->signatureMap as $interface => $methods) {
                foreach ($methods as $method) {
                    if ($method->name == $proceed->name) {
                        $methodExists = true;
                        break;
                    }
                }
            }
        }
        if ($methodExists) {
            return $this->interceptor->intercept(new Invocation($this->target, $proceed, $args));
        }
        return $proceed->invoke($this->target, ...$args);
    }

    public static function wrap($target, Interceptor $interceptor)
    {
        return PluginFactory::wrap($target, $interceptor);
    }
}
