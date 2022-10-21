<?php

namespace MyBatis\Scripting\XmlTags;

class MixedSqlNode implements SqlNodeInterface
{
    private $contents = [];

    public function __construct(array $contents)
    {
        $this->contents = $contents;
    }

    public function apply(DynamicContext $context): bool
    {
        foreach ($this->contents as $node) {
            $node->apply($context);
        }
        return true;
    }
}
