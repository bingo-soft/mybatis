<?php

namespace MyBatis\Scripting\XmlTags;

use MyBatis\Builder\{
    BaseBuilder,
    BuilderException
};
use MyBatis\Mapping\SqlSourceInterface;
use MyBatis\Parsing\XNode;
use MyBatis\Scripting\Defaults\RawSqlSource;
use MyBatis\Session\Configuration;

class XMLScriptBuilder extends BaseBuilder
{
    private $context;
    private $isDynamic = false;
    private $parameterType;
    private $nodeHandlerMap = [];

    public function __construct(Configuration $configuration, XNode $context, ?string $parameterType = null)
    {
        parent::__construct($configuration);
        $this->context = $context;
        $this->parameterType = $parameterType;
        $this->initNodeHandlerMap();
    }

    private function initNodeHandlerMap(): void
    {
        $this->nodeHandlerMap["trim"] = new TrimHandler($this);
        $this->nodeHandlerMap["where"] = new WhereHandler($this);
        $this->nodeHandlerMap["set"] = new SetHandler($this);
        $this->nodeHandlerMap["foreach"] = new ForEachHandler($this);
        $this->nodeHandlerMap["if"] = new IfHandler($this);
        $this->nodeHandlerMap["choose"] = new ChooseHandler($this);
        $this->nodeHandlerMap["when"] = new IfHandler($this);
        $this->nodeHandlerMap["otherwise"] = new OtherwiseHandler($this);
        $this->nodeHandlerMap["bind"] = new BindHandler($this);
    }

    public function parseScriptNode(): SqlSourceInterface
    {
        $rootSqlNode = $this->parseDynamicTags($this->context);
        $sqlSource = null;
        if ($this->isDynamic) {
            $sqlSource = new DynamicSqlSource($this->configuration, $rootSqlNode);
        } else {
            $sqlSource = new RawSqlSource($this->configuration, $rootSqlNode, $this->parameterType);
        }
        return $sqlSource;
    }

    public function parseDynamicTags(XNode $node): MixedSqlNode
    {
        $contents = [];
        $children = $node->getNode()->childNodes;
        for ($i = 0; $i < count($children); $i += 1) {
            $child = $node->newXNode($children[$i]);
            if ($child->getNode()->nodeType == XML_CDATA_SECTION_NODE || $child->getNode()->nodeType == XML_TEXT_NODE) {
                $data = $child->getStringBody("");
                $textSqlNode = new TextSqlNode($data);
                if ($textSqlNode->isDynamic()) {
                    $contents[] = $textSqlNode;
                    $this->isDynamic = true;
                } else {
                    $contents[] = new StaticTextSqlNode($data);
                }
            } elseif ($child->getNode()->nodeType == XML_ELEMENT_NODE) {
                $nodeName = $child->getNode()->nodeName;
                $handler = null;
                if (array_key_exists($nodeName, $this->nodeHandlerMap)) {
                    $handler = $this->nodeHandlerMap[$nodeName];
                }
                if ($handler == null) {
                    throw new BuilderException("Unknown element <" . $nodeName . "> in SQL statement.");
                }
                $handler->handleNode($child, $contents);
                $this->isDynamic = true;
            }
        }
        return new MixedSqlNode($contents);
    }

    public function getNodeHandler(string $key)
    {
        if (array_key_exists($nodeName, $this->nodeHandlerMap)) {
            return $this->nodeHandlerMap[$nodeName];
        }
        return null;
    }
}
