<?php

namespace MyBatis\Scripting\XmlTags;

class VarDeclSqlNode implements SqlNodeInterface
{
    private $name;
    private $expression;

    public function __construct(string $name, string $exp)
    {
        $this->name = $name;
        $this->expression = $exp;
    }

    public function apply(DynamicContext $context): bool
    {
        $expression = $this->expression;
        if (strpos($expression, '${') === false) {
            $expression = '${' .  $expression . '}';
        }
        $value = JuelCache::getValue($expression, $context->getBindings());
        $context->bind($this->name, $value);
        return true;
    }
}
