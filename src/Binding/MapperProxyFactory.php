<?php

namespace MyBatis\Binding;

use MyBatis\Session\SqlSessionInterface;
use Util\Proxy\Proxy;

class MapperProxyFactory
{
    private $mapperInterface;
    private $methodCache = [];

    public function __construct(string $mapperInterface)
    {
        $this->mapperInterface = $mapperInterface;
    }

    public function getMapperInterface(): string
    {
        return $this->mapperInterface;
    }

    public function getMethodCache(): array
    {
        return $this->methodCache;
    }

    public function newInstance(/*SqlSessionInterface|MapperProxy*/$sessionOrProxy)
    {
        if ($sessionOrProxy instanceof SqlSessionInterface) {
            $mapperProxy = new MapperProxy($sessionOrProxy, $this->mapperInterface, $this->methodCache);
            return $this->newInstance($mapperProxy);
        } elseif ($sessionOrProxy instanceof MapperProxy) {
            return Proxy::newProxyInstance([ $this->mapperInterface ], $sessionOrProxy);
        }
    }
}
