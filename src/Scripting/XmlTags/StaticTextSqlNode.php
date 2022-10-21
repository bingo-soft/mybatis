<?php

namespace MyBatis\Scripting\XmlTags;

class StaticTextSqlNode implements SqlNodeInterface
{
    private $text;

    public function __construct(string $text)
    {
        $this->text = $text;
    }

    public function apply(DynamicContext $context): bool
    {
        $context->appendSql($this->text);
        return true;
    }
}
