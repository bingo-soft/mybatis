<?php

namespace MyBatis\Scripting\XmlTags;

use MyBatis\Parsing\XNode;

class TrimHandler implements NodeHandlerInterface
{
    private $scope;

    public function __construct(XMLScriptBuilder $scope)
    {
        $this->scope = $scope;
    }

    public function handleNode(XNode $nodeToHandle, array &$targetContents): void
    {
        $mixedSqlNode = $this->scope->parseDynamicTags($nodeToHandle);
        $prefix = $nodeToHandle->getStringAttribute("prefix");
        $prefixOverrides = $nodeToHandle->getStringAttribute("prefixOverrides");
        $suffix = $nodeToHandle->getStringAttribute("suffix");
        $suffixOverrides = $nodeToHandle->getStringAttribute("suffixOverrides");
        $trim = new TrimSqlNode($this->scope->getConfiguration(), $mixedSqlNode, $prefix, $prefixOverrides, $suffix, $suffixOverrides);
        $targetContents[] = $trim;
    }
}
