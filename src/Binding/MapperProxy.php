<?php

namespace MyBatis\Binding;

use MyBatis\Session\SqlSessionInterface;
use Util\Proxy\MethodHandlerInterface;

class MapperProxy implements MethodHandlerInterface
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

    public function invoke($proxy, \ReflectionMethod $thisMethod, \ReflectionMethod $proceed, array $args)
    {
        if ($proceed->getDeclaringClass() == __CLASS__) {
            return $proceed->invoke($this, ...$args);
        } else {
            return $this->cachedInvoker($proceed)->invoke($proxy, $proceed, $args, $this->sqlSession);
        }
    }

    private function cachedInvoker(\ReflectionMethod $method): MapperMethodInvokerInterface
    {
        return new PlainMethodInvoker(new MapperMethod($this->mapperInterface, $method, $this->sqlSession->getConfiguration()));
    }
}
