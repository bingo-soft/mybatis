<?php

namespace MyBatis\Binding;

use MyBatis\Reflection\ParamNameResolver;
use MyBatis\Session\{
    Configuration,
    ResultHandlerInterface,
    RowBounds
};

class MethodSignature
{
    private $returnsMany;
    private $returnsMap;
    private $returnsVoid;
    private $returnsCursor;
    private $returnsOptional;
    private $returnType;
    //private $mapKey;
    private $resultHandlerIndex;
    private $rowBoundsIndex;
    private $paramNameResolver;

    public function __construct(Configuration $configuration, string $mapperInterface, \ReflectionMethod $method)
    {
        $resolvedReturnType = null;
        $retType = $method->getReturnType();
        if ($retType instanceof \ReflectionNamedType) {
            $this->returnType = $retType->getName();
        }
        $this->returnsVoid = $this->returnType == 'void';
        $this->returnsMany = $this->returnType == 'array';
        $this->returnsCursor = false;
        $this->returnsOptional = false;
        $this->returnsMap = $this->returnType == 'array';
        $this->rowBoundsIndex = $this->getUniqueParamIndex($method, RowBounds::class);
        $this->resultHandlerIndex = $this->getUniqueParamIndex($method, ResultHandlerInterface::class);
        $this->paramNameResolver = new ParamNameResolver($configuration, $method);
    }

    public function convertArgsToSqlCommandParam(array $args)
    {
        $ret = $this->paramNameResolver->getNamedParams($args);
        return $ret;
    }

    public function hasRowBounds(): bool
    {
        return $this->rowBoundsIndex !== null;
    }

    public function extractRowBounds(array $args): RowBounds
    {
        return $this->hasRowBounds() ? $args[$this->rowBoundsIndex] : null;
    }

    public function hasResultHandler(): bool
    {
        return $this->resultHandlerIndex !== null;
    }

    public function extractResultHandler(array $args): ResultHandlerInterface
    {
        return $this->hasResultHandler() ? $args[$this->resultHandlerIndex] : null;
    }

    public function getReturnType(): string
    {
        return $this->returnType;
    }

    public function returnsMany(): bool
    {
        return $this->returnsMany;
    }

    public function returnsMap(): bool
    {
        return $this->returnsMap;
    }

    public function returnsVoid(): bool
    {
        return $this->returnsVoid;
    }

    public function returnsCursor(): bool
    {
        return $this->returnsCursor;
    }

    public function returnsOptional(): bool
    {
        return $this->returnsOptional;
    }

    private function getUniqueParamIndex(\ReflectionMethod $method, string $paramType): ?int
    {
        $index = null;
        $argTypes = $method->getParameters();
        for ($i = 0; $i < count($argTypes); $i += 1) {
            $argType = $argTypes[$i]->getType();
            if ($argType instanceof \ReflectionNamedType && $paramType == $argType->getName()) {
                if ($index === null) {
                    $index = $i;
                } else {
                    throw new BindingException($method->getName() . " cannot have multiple $paramType parameters");
                }
            }
        }
        return $index;
    }
}
