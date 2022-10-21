<?php

namespace MyBatis\Scripting\XmlTags;

use MyBatis\Parsing\XNode;

class BindHandler implements NodeHandlerInterface
{
    private $scope;

    public function __construct(XMLScriptBuilder $scope)
    {
        $this->scope = $scope;
    }

    public function handleNode(XNode $nodeToHandle, array &$targetContents): void
    {
        $name = $nodeToHandle->getStringAttribute("name");
        $expression = $nodeToHandle->getStringAttribute("value");
        $node = new VarDeclSqlNode($name, $expression);
        $targetContents[] = $node;
    }
}
