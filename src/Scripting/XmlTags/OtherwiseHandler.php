<?php

namespace MyBatis\Scripting\XmlTags;

use MyBatis\Parsing\XNode;

class OtherwiseHandler implements NodeHandlerInterface
{
    private $scope;

    public function __construct(XMLScriptBuilder $scope)
    {
        $this->scope = $scope;
    }

    public function handleNode(XNode $nodeToHandle, array &$targetContents): void
    {
        $mixedSqlNode = $this->scope->parseDynamicTags($nodeToHandle);
        $targetContents[] = $mixedSqlNode;
    }
}
