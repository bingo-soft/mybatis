<?php

namespace MyBatis\Reflection\Invoker;

interface InvokerInterface
{
    public function invoke($target, array $args = []);

    public function getType(): ?string;
}
