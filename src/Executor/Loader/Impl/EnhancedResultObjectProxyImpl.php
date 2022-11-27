<?php

namespace MyBatis\Executor\Loader\Impl;

use MyBatis\Executor\Loader\ResultLoaderMap;
use Util\Reflection\Property\{
    PropertyCopier,
    PropertyNamer
};
use MyBatis\Session\Configuration;
use Util\Proxy\MethodHandlerInterface;

class EnhancedResultObjectProxyImpl implements MethodHandlerInterface
{
    private $type;
    private $lazyLoader;
    private $aggressive;
    private $lazyLoadTriggerMethods = [];
    private $constructorArgs = [];

    public function __construct(string $type, ResultLoaderMap $lazyLoader, Configuration $configuration, array $constructorArgs)
    {
        $this->type = $type;
        $this->lazyLoader = $lazyLoader;
        $this->aggressive = $configuration->isAggressiveLazyLoading();
        $this->lazyLoadTriggerMethods = $configuration->getLazyLoadTriggerMethods();
        $this->constructorArgs = $constructorArgs;
    }

    public static function createProxy($target, ResultLoaderMap $lazyLoader, Configuration $configuration, array $constructorArgs)
    {
        $type = get_class($target);
        $callback = new EnhancedResultObjectProxyImpl($type, $lazyLoader, $configuration, $constructorArgs);
        $enhanced = ProxyFactoryImpl::crateProxy($type, $callback, $constructorArgs);
        PropertyCopier::copyObjectProperties($type, $target, $enhanced);
        return $enhanced;
    }

    public function invoke($enhanced, \ReflectionMethod $method, \ReflectionMethod $methodProxy, array $args)
    {
        $methodName = $method->name;
        if ($this->lazyLoader->size() > 0) {
            if ($this->aggressive || in_array($methodName, $this->lazyLoadTriggerMethods)) {
                $this->lazyLoader->loadAll();
            } elseif (PropertyNamer::isSetter($methodName)) {
                $property = PropertyNamer::methodToProperty($methodName);
                $this->lazyLoader->remove($property);
            } elseif (PropertyNamer::isGetter($methodName)) {
                $property = PropertyNamer::methodToProperty($methodName);
                if ($this->lazyLoader->hasLoader($property)) {
                    $this->lazyLoader->load($property);
                }
            }
        }
        return $methodProxy->invoke($enhanced, ...$args);
    }
}
