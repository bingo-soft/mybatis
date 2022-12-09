<?php

namespace MyBatis\Plugin;

interface InterceptorInterface
{
    public function intercept(Invocation $invocation);

    public function plugin($target);

    public function setProperties(array $properties): void;
}
