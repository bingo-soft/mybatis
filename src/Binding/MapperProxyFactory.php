<?php

namespace MyBatis\Binding;

use MyBatis\Session\SqlSessionInterface;
use Util\Proxy\ProxyFactory;

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

    public function newInstance(/*SqlSessionInterface|MapperProxy*/$sessionOrHandler)
    {
        if ($sessionOrHandler instanceof SqlSessionInterface) {
            $mapperProxy = new MapperProxy($sessionOrHandler, $this->mapperInterface, $this->methodCache);
            return $this->newInstance($mapperProxy);
        } elseif ($sessionOrHandler instanceof MapperProxy) {
            $enhancer = new ProxyFactory();
            $enhancer->setSuperclass(MapperProxy::class);
            $enhancer->setInterfaces([ $this->mapperInterface ]);
            $proxy = $enhancer->create([]);
            $proxy->setHandler($sessionOrHandler);
            return $proxy;
        }
    }
}
