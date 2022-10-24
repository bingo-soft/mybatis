<?php

namespace MyBatis\Plugin;

class InterceptorChain
{
    private $interceptors = [];

    public function pluginAll($target)
    {
        foreach ($this->interceptors as $interceptor) {
            $target = $interceptor->plugin($target);
        }
        return $target;
    }

    public function addInterceptor(Interceptor $interceptor): void
    {
        $this->interceptors[] = $interceptor;
    }

    public function getInterceptors(): array
    {
        return $this->interceptors;
    }
}
