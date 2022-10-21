<?php

namespace MyBatis\Scripting\XmlTags;

use MyBatis\Parsing\GenericTokenParser;

class TextSqlNode implements SqlNodeInterface
{
    private $text;
    private $injectionFilter;

    public function __construct(string $text, ?string $injectionFilter = null)
    {
        $this->text = $text;
        $this->injectionFilter = $injectionFilter;
    }

    public function isDynamic(): bool
    {
        $checker = new DynamicCheckerTokenParser();
        $parser = $this->createParser($checker);
        $parser->parse($this->text);
        return $checker->isDynamic();
    }

    public function apply(DynamicContext $context): bool
    {
        $parser = $this->createParser(new BindingTokenParser($context, $this->injectionFilter));
        $context->appendSql($parser->parse($this->text));
        return true;
    }

    private function createParser(TokenHandlerInterface $handler): GenericTokenParser
    {
        return new GenericTokenParser("${", "}", $handler);
    }
}
