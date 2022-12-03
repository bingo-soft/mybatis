<?php

namespace MyBatis\Binding;

use MyBatis\Session\SqlSessionInterface;
use Util\Proxy\MethodHandlerInterface;
use Util\Reflection\MapUtil;

class MapperProxy implements MethodHandlerInterface
{
    private $mapperRefInterface;

    public function __construct(private SqlSessionInterface $sqlSession, private string $mapperInterface, private MethodCache $methodCache)
    {
    }

    public function __call(string $name, array $args)
    {
        if ($this->mapperRefInterface === null) {
            $this->mapperRefInterface = new \ReflectionClass($this->mapperInterface);
        }
        if ($this->mapperRefInterface->hasMethod($name)) {
            $method = $this->mapperRefInterface->getMethod($name);
            return $this->invoke($this, $method, $method, $args);
        }
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
        $mapperInterface = $this->mapperInterface;
        $sqlSession = $this->sqlSession;
        $ret = &MapUtil::computeIfAbsent($this->methodCache, $method->name, function () use ($mapperInterface, $method, $sqlSession) {
            return new PlainMethodInvoker(new MapperMethod($this->mapperInterface, $method, $this->sqlSession->getConfiguration()));
        });
        return $ret;
    }
}
