<?php

namespace MyBatis\Builder\Annotation;

class MethodResolver
{
    private $annotationBuilder;
    private $method;

    public function __construct(MapperAnnotationBuilder $annotationBuilder, \ReflectionMethod $method)
    {
        $this->annotationBuilder = $annotationBuilder;
        $this->method = $method;
    }

    public function resolve(): void
    {
        $this->annotationBuilder->parseStatement($method);
    }
}
