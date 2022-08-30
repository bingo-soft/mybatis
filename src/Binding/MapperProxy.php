<?php

namespace MyBatis\Binding;

use MyBatis\Session\SqlSessionInterface;
use Util\Proxy\InvocationHandlerInterface;

class MapperProxy implements InvocationHandlerInterface
{
    private $sqlSession;
    private $mapperInterface;
    private $methodCache = [];

    public function __construct(SqlSessionInterface $sqlSession, string $mapperInterface, array $methodCache = [])
    {
        $this->sqlSession = $sqlSession;
        $this->mapperInterface = $mapperInterface;
        $this->methodCache = $methodCache;
    }

    public function invoke($proxy, \ReflectionMethod $method, array $args)
    {
        if ($method->getDeclaringClass() == __CLASS__) {
            return $method->invoke($this, $args);
        } else {
            return $this->cachedInvoker($method)->invoke($proxy, $method, $args, $this->sqlSession);
        }
    }

    private function cachedInvoker(\ReflectionMethod $method): MapperMethodInvokerInterface
    {
        return new PlainMethodInvoker(new MapperMethod($this->mapperInterface, $method, $this->sqlSession->getConfiguration()));
    }
}
