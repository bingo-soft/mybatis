<?php

namespace MyBatis\Binding;

use MyBatis\Session\SqlSessionInterface;

class DefaultMethodInvoker implements MapperMethodInvokerInterface
{
    public function invoke($proxy, \ReflectionMethod $method, array $args, SqlSessionInterface $sqlSession)
    {
        //
    }
}
