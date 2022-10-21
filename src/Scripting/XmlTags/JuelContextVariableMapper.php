<?php

namespace MyBatis\Scripting\XmlTags;

use El\{
    ValueExpression,
    VariableMapper
};

class JuelContextVariableMapper extends VariableMapper
{
    private $scriptContext;

    public function __construct(ContextMap $scriptCtx)
    {
        $this->scriptContext = $scriptCtx;
    }

    public function resolveVariable(string $variableName): ?ValueExpression
    {
        $value = $this->scriptContext->get($variableName);
        if ($value instanceof ValueExpression) {
            // Just return the existing ValueExpression
            return $value;
        } else {
            // Create a new ValueExpression based on the variable value
            return JuelCache::getExpressionFactory()->createValueExpression(JuelCache::getElContext($this->scriptContext), null, $value, "object");
        }
    }

    public function setVariable(string $variable, ValueExpression $expression): ValueExpression
    {
        $previousValue = $this->resolveVariable($name);
        $this->scriptContext->put($name, $value);
        return $previousValue;
    }
}
