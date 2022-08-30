<?php

namespace MyBatis\Binding;

use MyBatis\Session\SqlSessionInterface;

class PlainMethodInvoker implements MapperMethodInvokerInterface
{
    private $mapperMethod;

    public function __construct(MapperMethod $mapperMethod)
    {
        $this->mapperMethod = $mapperMethod;
    }

    public function invoke($proxy, \ReflectionMethod $method, array $args, SqlSessionInterface $sqlSession)
    {
        return $this->mapperMethod->execute($sqlSession, $args);
    }
}
