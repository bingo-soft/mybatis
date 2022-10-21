<?php

namespace MyBatis\Scripting\XmlTags;

use MyBatis\Parsing\XNode;

class SetHandler implements NodeHandlerInterface
{
    private $scope;

    public function __construct(XMLScriptBuilder $scope)
    {
        $this->scope = $scope;
    }

    public function handleNode(XNode $nodeToHandle, array &$targetContents): void
    {
        $mixedSqlNode = $this->scope->parseDynamicTags($nodeToHandle);
        $set = new SetSqlNode($this->scope->getConfiguration(), $mixedSqlNode);
        $targetContents[] = $set;
    }
}
