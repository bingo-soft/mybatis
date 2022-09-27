<?php

namespace MyBatis\Executor\Loader;

use MyBatis\Session\Configuration;

interface ProxyFactoryInterface
{
    public function setProperties(array $properties): void;

    public function createProxy($target, ResultLoaderMap $lazyLoader, Configuration $configuration, array $constructorArgs);
}
