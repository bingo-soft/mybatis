<?php

namespace MyBatis\Binding;

use MyBatis\Session\SqlSessionInterface;

interface MapperMethodInvokerInterface
{
    public function invoke($proxy, \ReflectionMethod $method, array $args, SqlSessionInterface $sqlSession);
}
