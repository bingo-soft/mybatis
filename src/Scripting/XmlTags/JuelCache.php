<?php

namespace MyBatis\Scripting\XmlTags;

use El\{
    ArrayELResolver,
    ObjectELResolver,
    CompositeELResolver,
    ELContext,
    ELException,
    ELResolver,
    ExpressionFactory,
    FunctionMapper,
    ValueExpression,
    VariableMapper
};
use Juel\{
    SimpleResolver,
    ExpressionFactoryImpl
};
use Script\{
    AbstractScriptEngine,
    BindingsInterface,
    ScriptContextInterface,
    ScriptEngineInterface,
    ScriptEngineFactoryInterface,
    ScriptException,
    SimpleBindings,
    SimpleScriptContext
};
use Util\Reflection\MetaObject;

class JuelCache
{
    private static $expressionFactory;

    private static $isArrayContext = false;

    private function __construct()
    {
    }

    public static function getValue(string $expression, /*ContextMap*/$root)
    {
        if (self::$expressionFactory === null) {
            self::$expressionFactory = new ExpressionFactoryImpl();
        }
        if (!($root instanceof ContextMap)) {
            if (is_array($root) || is_iterable($root)) {
                $context = new ContextMap($root, false);
                self::$isArrayContext = true;
            } else {
                if (!($root instanceof MetaObject)) {
                    $root = new MetaObject($root);
                }
                $context = new ContextMap($root);
            }
        } else {
            $context = $root;
        }
        $expr = self::parse($expression, $context);
        return self::evaluateExpression($expr, $context);
    }

    public static function getExpressionFactory(): ExpressionFactoryImpl
    {
        if (self::$expressionFactory === null) {
            self::$expressionFactory = new ExpressionFactoryImpl();
        }
        return self::$expressionFactory;
    }

    private static function parse(string $script, ContextMap $scriptContext): ValueExpression
    {
        return self::$expressionFactory->createValueExpression(self::getElContext($scriptContext), $script, null, "object");
    }

    public static function evaluateExpression(ValueExpression $expr, ContextMap $ctx)
    {
        $context = self::getElContext($ctx);
        return $expr->getValue($context);
    }

    public static function createElResolver(): ELResolver
    {
        $compositeResolver = new CompositeELResolver();
        $compositeResolver->add(new ArrayELResolver());
        $compositeResolver->add(new ObjectELResolver());
        return new SimpleResolver($compositeResolver);
    }

    public static function getElContext(ContextMap $scriptCtx): ELContext
    {
        $existingELCtx = $scriptCtx->get("elcontext");
        if ($existingELCtx instanceof ELContext) {
            return $existingELCtx;
        }

        $elContext = new class ($scriptCtx) extends ELContext {
            private $resolver;
            private $varMapper;
            private $funcMapper;

            public function __construct(ContextMap $scriptCtx)
            {
                $this->resolver = JuelCache::createElResolver();
                $this->varMapper = new JuelContextVariableMapper($scriptCtx);
                $this->funcMapper = new JuelContextFunctionMapper($scriptCtx);
            }

            public function getELResolver(): ?ELResolver
            {
                return $this->resolver;
            }

            public function getVariableMapper(): ?VariableMapper
            {
                return $this->varMapper;
            }

            public function getFunctionMapper(): ?FunctionMapper
            {
                return $this->funcMapper;
            }
        };
        $scriptCtx->put("elcontext", $elContext);
        return $elContext;
    }
}
