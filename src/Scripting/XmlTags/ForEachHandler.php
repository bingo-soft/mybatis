<?php

namespace MyBatis\Scripting\XmlTags;

use MyBatis\Parsing\XNode;

class ForEachHandler implements NodeHandlerInterface
{
    private $scope;

    public function __construct(XMLScriptBuilder $scope)
    {
        $this->scope = $scope;
    }

    public function handleNode(XNode $nodeToHandle, array &$targetContents): void
    {
        $mixedSqlNode = $this->scope->parseDynamicTags($nodeToHandle);
        $collection = $nodeToHandle->getStringAttribute("collection");
        $nullable = $nodeToHandle->getBooleanAttribute("nullable");
        $item = $nodeToHandle->getStringAttribute("item");
        $index = $nodeToHandle->getStringAttribute("index");
        $open = $nodeToHandle->getStringAttribute("open");
        $close = $nodeToHandle->getStringAttribute("close");
        $separator = $nodeToHandle->getStringAttribute("separator");
        $forEachSqlNode = new ForEachSqlNode($this->scope->getConfiguration(), $mixedSqlNode, $collection, $nullable, $index, $item, $open, $close, $separator);
        $targetContents[] = $forEachSqlNode;
    }
}
