<?php

namespace MyBatis\Scripting\XmlTags;

use El\FunctionMapper;

class JuelContextFunctionMapper extends FunctionMapper
{
    private $scriptContext;

    public function __construct(ContextMap $ctx)
    {
        $this->scriptContext = $ctx;
    }

    private function getFullFunctionName(string $prefix, string $localName): string
    {
        return $prefix . ":" . $localName;
    }

    public function resolveFunction(string $prefix, string $localName): ?\ReflectionMethod
    {
        $functionName = $this->getFullFunctionName($prefix, $localName);
        $attributeValue = $this->scriptContext->get($functionName);
        return ($attributeValue instanceof \ReflectionMethod) ? $attributeValue : null;
    }
}
