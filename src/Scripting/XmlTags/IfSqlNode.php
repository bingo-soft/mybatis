<?php

namespace MyBatis\Scripting\XmlTags;

class IfSqlNode implements SqlNodeInterface
{
    private $evaluator;
    private $test;
    private $contents;

    public function __construct(SqlNodeInterface $contents, string $test)
    {
        $this->test = $test;
        $this->contents = $contents;
        $this->evaluator = new ExpressionEvaluator();
    }

    public function apply(DynamicContext $context): bool
    {
        if ($this->evaluator->evaluateBoolean($this->test, $context->getBindings())) {
            $this->contents->apply($context);
            return true;
        }
        return false;
    }
}
