<?php

namespace MyBatis\Scripting\XmlTags;

use MyBatis\Builder\BuilderException;
use MyBatis\Parsing\XNode;

class ChooseHandler implements NodeHandlerInterface
{
    private $scope;

    public function __construct(XMLScriptBuilder $scope)
    {
        $this->scope = $scope;
    }

    public function handleNode(XNode $nodeToHandle, array &$targetContents): void
    {
        $whenSqlNodes = [];
        $otherwiseSqlNodes = [];
        $this->handleWhenOtherwiseNodes($nodeToHandle, $whenSqlNodes, $otherwiseSqlNodes);
        $defaultSqlNode = $this->getDefaultSqlNode($otherwiseSqlNodes);
        $chooseSqlNode = new ChooseSqlNode($whenSqlNodes, $defaultSqlNode);
        $targetContents[] = $chooseSqlNode;
    }

    private function handleWhenOtherwiseNodes(XNode $chooseSqlNode, array $ifSqlNodes, array $defaultSqlNodes): void
    {
        $children = $chooseSqlNode->getChildren();
        foreach ($children as $child) {
            $nodeName = $child->getNode()->getNodeName();
            $handler = $this->scope->getNodeHandler($nodeName);
            if ($handler instanceof IfHandler) {
                $handler->handleNode($child, $ifSqlNodes);
            } elseif ($handler instanceof OtherwiseHandler) {
                $handler->handleNode($child, $defaultSqlNodes);
            }
        }
    }

    private function getDefaultSqlNode(array $defaultSqlNodes): ?SqlNodeInterface
    {
        $defaultSqlNode = null;
        if (count($defaultSqlNodes) == 1) {
            $defaultSqlNode = $defaultSqlNodes[0];
        } elseif (count($defaultSqlNodes) > 1) {
            throw new BuilderException("Too many default (otherwise) elements in choose statement.");
        }
        return $defaultSqlNode;
    }
}
