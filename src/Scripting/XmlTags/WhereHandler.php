<?php

namespace MyBatis\Scripting\XmlTags;

use MyBatis\Parsing\XNode;

class WhereHandler implements NodeHandlerInterface
{
    private $scope;

    public function __construct(XMLScriptBuilder $scope)
    {
        $this->scope = $scope;
    }

    public function handleNode(XNode $nodeToHandle, array &$targetContents): void
    {
        $mixedSqlNode = $this->scope->parseDynamicTags($nodeToHandle);
        $where = new WhereSqlNode($this->scope->getConfiguration(), $mixedSqlNode);
        $targetContents[] = $where;
    }
}
