<?php

namespace MyBatis\Parsing;

class XNode
{
    private $node;
    private $name;
    private $body;
    private $attributes;
    private $variables;
    private $xpathParser;

    public function __construct(XPathParser $xpathParser, \DOMNode $node, $variables)
    {
        $this->xpathParser = $xpathParser;
        $this->node = $node;
        $this->name = $node->nodeName;
        $this->variables = $variables;
        $this->attributes = $this->parseAttributes($node);
        $this->body = $this->parseBody($node);
    }

    public function newXNode(Node $node): XNode
    {
        return new XNode($this->xpathParser, $node, $this->variables);
    }

    public function getParent(): ?XNode
    {
        $parent = $this->node->parentNode;
        if (!($parent instanceof \DOMElement)) {
            return null;
        } else {
            return new XNode($this->xpathParser, $parent, $this->variables);
        }
    }

    public function getPath(): string
    {
        $builder = "";
        $current = $this->node;
        while ($current instanceof \DOMElement) {
            if ($current !== $this->node) {
                $builder = "/" . $builder;
            }
            $builder = $current->nodeName . $builder;
            $current = $current->parentNode;
        }
        return $builder;
    }

    public function getValueBasedIdentifier(): string
    {
        $builder = "";
        $current = $this;
        while ($current !== null) {
            if ($current != $this) {
                $builder = "_" . $builder;
            }
            $value = $current->getStringAttribute("id", $current->getStringAttribute("value", $current->getStringAttribute("property")));
            if ($value !== null) {
                $value = str_replace('.', '_', $value);
                $builder = "[" . $value . "]" . $builder;
            }
            $builder = $current->node->nodeName . $builder;
            $current = $current->getParent();
        }
        return $builder;
    }

    public function evalString(string $expression): string
    {
        return $this->xpathParser->evalString($this->node, $expression);
    }

    public function evalBoolean(string $expression): bool
    {
        return $this->xpathParser->evalBoolean($this->node, $expression);
    }

    public function evalDouble(string $expression): float
    {
        return $this->xpathParser->evalDouble($this->node, $expression);
    }

    public function evalNodes(string $expression): array
    {
        return $this->xpathParser->evalNodes($this->node, $expression);
    }

    public function evalNode(string $expression): XNode
    {
        return $this->xpathParser->evalNode($this->node, $expression);
    }

    public function getNode(): \DOMNode
    {
        return $this->node;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getStringBody(string $def = null): ?string
    {
        return $this->body === null ? $this->def : $this->body;
    }

    public function getBooleanBody(bool $def = false): ?bool
    {
        return $this->body === null ? $def : Boolean::parseBoolean($this->body);
    }

    public function getIntBody(int $def = null): ?int
    {
        return $this->body === null ? $def : intval($this->body);
    }

    public function getLongBody(int $def = null): ?int
    {
        return $this->body === null ? $def : intval($this->body);
    }

    public function getDoubleBody(float $def = null): ?float
    {
        return $this->body === null ? $def : floatval($this->body);
    }

    public function getFloatBody(float $def = null): ?float
    {
        return $this->body === null ? $def : floatval($this->body);
    }

    public function getEnumAttribute($enumType, string $name, $def = null)
    {
        $value = $this->getStringAttribute($name);
        if (is_object($enumType)) {
            return $value === null ? $def : $enumType::$value;
        } else {
            return $value === null ? $def : constant("$enumType::$value");
        }
    }

    /**
     * Return a attribute value as String.
     *
     * <p>
     * If attribute value is absent, return value that provided from supplier of default value.
     *
     * @param name
     *          attribute name
     * @param defSupplier
     *          a supplier of default value
     * @return the string attribute
     */
    public function getStringAttribute(string $name, $defSupplier = null)
    {
        $value = $this->getProperty($name);
        if ($value !== null) {
            return $value;
        }
        if (class_exists($defSupplier)) {
            return (new $defSupplier())->get();
        }
        return $defSupplier;
    }

    private function getProperty(string $name)
    {
        $value = null;
        foreach ($this->attributes as $curName => $curValue) {
            if ($name == $curName) {
                $value = $curValue;
                break;
            }
        }
        return $value;
    }

    public function getBooleanAttribute(string $name, $defSupplier = null): bool
    {
        return Boolean::parseBoolean($this->getStringAttribute($name, $defSupplier));
    }

    public function getIntAttribute(string $name, $defSupplier = null): int
    {
        return intval($this->getStringAttribute($name, $defSupplier));
    }

    public function getLongAttribute(string $name, $defSupplier = null): int
    {
        return intval($this->getStringAttribute($name, $defSupplier));
    }

    public function getDoubleAttribute(string $name, $defSupplier = null): float
    {
        return floatval($this->getStringAttribute($name, $defSupplier));
    }

    public function getFloatAttribute(string $name, $defSupplier = null): float
    {
        return floatval($this->getStringAttribute($name, $defSupplier));
    }

    public function getChildren(): array
    {
        $children = [];
        $nodeList = $this->node->childNodes;
        if (count($nodeList)) {
            for ($i = 0, $n = count($nodeList); $i < $n; $i += 1) {
                $node = $nodeList->item($i);
                if ($node !== null && $node->nodeType == XML_ELEMENT_NODE) {
                    $children[] = new XNode($this->xpathParser, $node, $this->variables);
                }
            }
        }
        return $children;
    }

    public function getChildrenAsProperties(): array
    {
        $properties = [];
        foreach ($this->getChildren() as $child) {
            $name = $child->getStringAttribute("name");
            $value = $child->getStringAttribute("value");
            if ($name !== null && $value !== null) {
                $properties[$name] = $value;
            }
        }
        return $properties;
    }

    public function __toString()
    {
        $builder = "";
        $this->toString($builder, 0);
        return $builder;
    }

    private function toString(string &$builder, int $level)
    {
        $builder .= "<";
        $builder .= $this->name;
        foreach ($this->attributes as $name => $value) {
            $builder .= " ";
            $builder .= $name;
            $builder .= "=\"";
            $builder .= $value;
            $builder .= "\"";
        }
        $children = $this->getChildren();
        if (!empty($children)) {
            $builder .= ">\n";
            foreach ($children as $child) {
                $this->indent($builder, $level + 1);
                $child->toString($builder, $level + 1);
            }
            $this->indent($builder, $level);
            $builder .= "</";
            $builder .= $this->name;
            $builder .= ">";
        } elseif ($this->body !== null) {
            $builder .= ">";
            $builder .= $this->body;
            $builder .= "</";
            $builder .= $this->name;
            $builder .= ">";
        } else {
            $builder .= "/>";
            $this->indent($builder, $level);
        }
        $builder .= "\n";
    }

    private function indent(string &$builder, int $level): void
    {
        for ($i = 0; $i < $level; $i += 1) {
            $builder .= "    ";
        }
    }

    private function parseAttributes(\DOMNode $n)
    {
        $attributes = [];
        $attributeNodes = $n->attributes;
        if ($attributeNodes !== null && count($attributeNodes)) {
            for ($i = 0; $i < count($attributeNodes); $i += 1) {
                $attribute = $attributeNodes->item($i);
                $value = PropertyParser::parse($attribute->nodeValue, $this->variables);
                $attributes[$attribute->nodeName] = $value;
            }
        }
        return $attributes;
    }

    private function parseBody(\DOMNode $node): ?string
    {
        $data = $this->getBodyData($node);
        if ($data === null) {
            $children = $node->childNodes;
            for ($i = 0; $i < count($children); $i += 1) {
                $child = $children->item($i);
                $data = $this->getBodyData($child);
                if ($data !== null) {
                    break;
                }
            }
        }
        return $data;
    }

    private function getBodyData(?\DOMNode $child): ?string
    {
        if ($child !== null && ($child->nodeType == XML_CDATA_SECTION_NODE || $child->nodeType == XML_TEXT_NODE)) {
            $data = $child->textContent; //?
            $data = PropertyParser::parse($data, $this->variables);
            return $data;
        }
        return null;
    }
}
