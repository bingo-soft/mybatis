<?php

namespace MyBatis\Parsing;

use Sax\EntityResolverInterface;

class XPathParser
{
    private $document;
    private $validation = false;
    private $entityResolver;
    private $variables;
    private $xpath;

    public function __construct($document, bool $validation = false, ?array $variables = [], ?EntityResolverInterface $entityResolver = null)
    {
        $type = null;
        try {
            if ($document instanceof \DOMDocument) {
                $type = 'document';
                $this->document = $document;
            } elseif (is_resource($document)) {
                $type = 'resource';
                $meta = stream_get_meta_data($document);
                $xmlString = fread($document, filesize($meta['uri']));
                $dom = new \DOMDocument();
                $dom->loadXML($xmlString);
                $this->document = $dom;
                fclose($document);
            } elseif (is_string($document)) {
                if (file_exists($document)) {
                    $type = 'path';
                    $fh = fopen($document, 'r+');
                    $meta = stream_get_meta_data($fh);
                    $xmlString = fread($fh, filesize($meta['uri']));
                    $dom = new \DOMDocument();
                    $dom->loadXML($xmlString);
                    $this->document = $dom;
                    fclose($fh);
                } else {
                    $type = 'contents';
                    $dom = new \DOMDocument();
                    $dom->loadXML($document);
                    $this->document = $dom;
                }
            }
            $this->commonConstructor($validation, $variables, $entityResolver);
        } catch (\Throwable $t) {
            $messages = [];
            for ($i = 0; $i < 10; $i += 1) {
                try {
                    $trace = $t->getTrace()[$i];
                    $messages[] = sprintf("%s.%s.%s", $trace['file'], $trace['function'], $trace['line']);
                } catch (\Exception $e) {
                    //ignore
                }
            }
            switch ($type) {
                case 'document':
                    throw new \Exception(sprintf("Corrupt document provided, stack: %s", implode(" <- ", $messages)));
                case 'resource':
                    throw new \Exception(sprintf("Corrupt resource provided, stack: %s", implode(" <- ", $messages)));
                case 'path':
                    throw new \Exception(sprintf("Corrupt file '%s' provided, stack: %s", $document, implode(" <- ", $messages)));
                case 'contents':
                    throw new \Exception(sprintf("Corrupt contents '%s' provided, stack: %s", $document, implode(" <- ", $messages)));
                default:
                    throw new \Exception(sprintf("Unknown error message found: %s, stack: %s", $t->getMessage(), implode(" <- ", $messages)));
            }
        }
    }

    public function setVariables(array $variables): void
    {
        $this->variables = $variables;
    }

    public function evalString($rootOrExpression, string $expression = null): ?string
    {
        if ($expression === null) {
            return $this->evalString($this->document, $rootOrExpression);
        } else {
            $result = $this->evaluate($expression, $rootOrExpression/*, XPathConstants.STRING*/);
            if ($result !== false) {
                $result = PropertyParser::parse($result->item(0)->nodeValue, $this->variables);
                return $result;
            }
            return null;
        }
    }

    public function evalBoolean($rootOrExpression, string $expression = null): ?bool
    {
        if ($expression === null) {
            return $this->evalBoolean($this->document, $rootOrExpression);
        }
        $nodes = $this->evaluate($expression, $rootOrExpression/*, XPathConstants.BOOLEAN*/);
        if ($nodes !== false) {
            return Boolean::parseBoolean($nodes->item(0)->nodeValue);
        }
        return null;
    }

    public function evalInteger($rootOrExpression, string $expression = null): int
    {
        if ($expression === null) {
            return $this->evalInteger($this->document, $rootOrExpression);
        }
        return intval($this->evalString($rootOrExpression, $expression));
    }

    public function evalShort($rootOrExpression, string $expression = null): int
    {
        return $this->evalInteger($rootOrExpression, $expression);
    }

    public function evalLong($rootOrExpression, string $expression = null): int
    {
        return $this->evalInteger($rootOrExpression, $expression);
    }

    public function evalFloat($rootOrExpression, string $expression = null): float
    {
        if ($expression === null) {
            return $this->evalFloat($this->document, $rootOrExpression);
        }
        return floatval($this->evalString($rootOrExpression, $expression));
    }

    public function evalDouble($rootOrExpression, string $expression = null): float
    {
        return $this->evalFloat($rootOrExpression, $expression);
    }

    public function evalNodes($rootOrExpression, string $expression = null): array
    {
        if ($expression === null) {
            return $this->evalNodes($this->document, $rootOrExpression);
        }
        $xnodes = [];
        $nodes = $this->evaluate($expression, $rootOrExpression/*, XPathConstants.NODESET*/);
        if ($nodes !== false) {
            for ($i = 0; $i < count($nodes); $i += 1) {
                $xnodes[] = new XNode($this, $nodes[$i], $this->variables);
            }
        }
        return $xnodes;
    }

    public function evalNode($rootOrExpression, string $expression = null): ?XNode
    {
        if ($expression === null) {
            return $this->evalNode($this->document, $rootOrExpression);
        }
        $node = $this->evaluate($expression, $rootOrExpression/*, XPathConstants.NODE*/);
        if ($node === null || $node === false || ($node instanceof \DOMNodeList && $node->length == 0)) {
            return null;
        }
        return new XNode($this, $node->item(0), $this->variables);
    }

    private function evaluate(string $expression, $root/*, QName returnType*/)
    {
        try {
            return $this->xpath->evaluate($expression, $root/*, returnType*/);
        } catch (\Exception $e) {
            throw new \Exception("Error evaluating XPath.  Cause: " . $e->getMessage());
        }
    }

    private function commonConstructor(bool $validation, ?array $variables = [], ?EntityResolverInterface $entityResolver = null): void
    {
        $this->validation = $validation;
        $this->entityResolver = $entityResolver;
        $this->variables = $variables;
        $this->xpath = new \DOMXpath($this->document);
    }
}
