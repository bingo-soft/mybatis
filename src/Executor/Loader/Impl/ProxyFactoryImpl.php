<?php

namespace MyBatis\Executor\Loader\Impl;

use MyBatis\Session\Configuration;
use MyBatis\Executor\ExecutorException;
use MyBatis\Executor\Loader\{
    ProxyFactoryInterface,
    ResultLoaderMap
};
use Util\Proxy\{
    MethodHandlerInterface,
    ProxyFactory
};

class ProxyFactoryImpl implements ProxyFactoryInterface
{
    public function setProperties(array $properties): void
    {
    }

    public function createProxy($target, ResultLoaderMap $lazyLoader, Configuration $configuration, array $constructorArgs)
    {
        return EnhancedResultObjectProxyImpl::createProxy($target, $lazyLoader, $configuration, $constructorArgs);
    }

    public static function crateProxy(string $type, MethodHandlerInterface $callback, array $constructorArgs)
    {
        $enhancer = new ProxyFactory();
        $enhancer->setSuperclass($type);

        $enhanced = null;
        try {
            $enhanced = $enhancer->create($constructorArgs);
        } catch (\Exception $e) {
            throw new ExecutorException("Error creating lazy proxy.  Cause: " . $e->getMessage());
        }
        $enhanced->setHandler($callback);
        return $enhanced;
    }
}
