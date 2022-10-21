<?php

namespace MyBatis\Scripting\XmlTags;

use MyBatis\Parsing\XNode;

class IfHandler implements NodeHandlerInterface
{
    private $scope;

    public function __construct(XMLScriptBuilder $scope)
    {
        $this->scope = $scope;
    }

    public function handleNode(XNode $nodeToHandle, array &$targetContents): void
    {
        $mixedSqlNode = $this->scope->parseDynamicTags($nodeToHandle);
        $test = $nodeToHandle->getStringAttribute("test");
        $ifSqlNode = new IfSqlNode($mixedSqlNode, $test);
        $targetContents[] = $ifSqlNode;
    }
}
