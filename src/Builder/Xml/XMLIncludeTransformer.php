<?php

namespace MyBatis\Builder\Xml;

use MyBatis\Builder\{
    BuilderException,
    IncompleteElementException,
    MapperBuilderAssistant
};
use MyBatis\Parsing\{
    PropertyParser,
    XNode
};
use MyBatis\Session\Configuration;

class XMLIncludeTransformer
{
    private $configuration;
    private $builderAssistant;

    public function __construct(Configuration $configuration, MapperBuilderAssistant $builderAssistant)
    {
        $this->configuration = $configuration;
        $this->builderAssistant = $builderAssistant;
    }

    /**
     * Recursively apply includes through all SQL fragments.
     *
     * @param source
     *          Include node in DOM tree
     * @param variablesContext
     *          Current context for static variables with values
     */
    public function applyIncludes(\DOMNode $source, array $variablesContext = [], bool $included = false): void
    {
        if (empty($variablesContext)) {
            $configurationVariables = $this->configuration->getVariables();
            if (!empty($configurationVariables)) {
                $variablesContext = array_merge($variablesContext, $configurationVariables);
            }
        }
        if ("include" == $source->nodeName) {
            $toInclude = $this->findSqlFragment($this->getStringAttribute($source, "refid"), $variablesContext);
            $toIncludeContext = $this->getVariablesContext($source, $variablesContext);
            $this->applyIncludes($toInclude, $toIncludeContext, true);
            if ($toInclude->ownerDocument !== $source->ownerDocument) {
                $toInclude = $source->ownerDocument->importNode($toInclude, true);
            }
            $source->parentNode->replaceChild($toInclude, $source);
            while ($toInclude->hasChildNodes()) {
                $toInclude->parentNode->insertBefore($toInclude->firstChild, $toInclude);
            }
            $toInclude->parentNode->removeChild($toInclude);
        } elseif ($source->nodeType == XML_ELEMENT_NODE) {
            if ($included && !empty($variablesContext)) {
                // replace variables in attribute values
                $attributes = $source->attributes;
                $cnt = count($source->attributes);
                for ($i = 0; $i < $cnt; $i += 1) {
                    $attr = $attributes->item($i);
                    $attr->nodeValue = PropertyParser::parse($attr->nodeValue, $variablesContext);
                }
            }
            $children = $source->childNodes;
            for ($i = 0; $i < count($source->childNodes); $i += 1) {
                $this->applyIncludes($children->item($i), $variablesContext, $included);
            }
        } elseif (
            $included && ($source->nodeType == XML_TEXT_NODE || $source->nodeType == XML_CDATA_SECTION_NODE)
            && !empty($variablesContext)
        ) {
            // replace variables in text node
            $source->nodeValue = PropertyParser::parse($source->nodeValue, $variablesContext);
        }
    }

    private function findSqlFragment(string $refid, array $variables): ?\DOMNode
    {
        $refid = PropertyParser::parse($refid, $variables);
        $refid = $this->builderAssistant->applyCurrentNamespace($refid, true);
        try {
            $frags = $this->configuration->getSqlFragments();
            if (array_key_exists($refid, $frags->getArrayCopy())) {
                $nodeToInclude = $frags[$refid];
                return $nodeToInclude->getNode()->cloneNode(true);
            }
            return null;
        } catch (\Exception $e) {
            throw new IncompleteElementException("Could not find SQL statement to include with refid '" . $refid . "'");
        }
    }

    private function getStringAttribute(\DOMNode $node, string $name): string
    {
        return $node->attributes->getNamedItem($name)->nodeValue;
    }

    /**
     * Read placeholders and their values from include node definition.
     *
     * @param node
     *          Include node instance
     * @param inheritedVariablesContext
     *          Current context used for replace variables in new variables values
     * @return variables context from include instance (no inherited values)
     */
    private function getVariablesContext(\DOMNode $node, array $inheritedVariablesContext): array
    {
        $declaredProperties = null;
        $children = $node->childNodes;
        for ($i = 0; $i < $children->count(); $i += 1) {
            $n = $children->item($i);
            if ($n->nodeType == XML_ELEMENT_NODE) {
                $name = $this->getStringAttribute($n, "name");
                // Replace variables inside
                $value = PropertyParser::parse($this->getStringAttribute($n, "value"), $inheritedVariablesContext);
                if ($declaredProperties == null) {
                    $declaredProperties = [];
                }
                if (array_key_exists($name, $declaredProperties)) {
                    throw new BuilderException("Variable " . $name . " defined twice in the same include definition");
                } else {
                    $declaredProperties[$name] = $value;
                }
            }
        }
        if ($declaredProperties == null) {
            return $inheritedVariablesContext;
        } else {
            $newProperties = array_merge($inheritedVariablesContext, $declaredProperties);
            return $newProperties;
        }
    }
}
