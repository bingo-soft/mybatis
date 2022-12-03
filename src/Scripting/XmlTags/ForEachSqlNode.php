<?php

namespace MyBatis\Scripting\XmlTags;

use MyBatis\Session\Configuration;

class ForEachSqlNode implements SqlNodeInterface
{
    public const ITEM_PREFIX = "__frch_";

    private $evaluator;
    private $collectionExpression;
    private $nullable;
    private $contents;
    private $open;
    private $close;
    private $separator;
    private $item;
    private $index;
    private $configuration;

    public function __construct(Configuration $configuration, SqlNodeInterface $contents, string $collectionExpression, bool $nullable, ?string $index, ?string $item, ?string $open, ?string $close, ?string $separator)
    {
        $this->evaluator = new ExpressionEvaluator();
        $this->collectionExpression = $collectionExpression;
        $this->nullable = $nullable;
        $this->contents = $contents;
        $this->open = $open;
        $this->close = $close;
        $this->separator = $separator;
        $this->index = $index;
        $this->item = $item;
        $this->configuration = $configuration;
    }

    public function apply(DynamicContext $context): bool
    {
        $bindings = $context->getBindings();
        $iterable = $this->evaluator->evaluateIterable($this->collectionExpression, $bindings);
        if (empty($iterable)) {
            return true;
        }
        $first = true;
        $this->applyOpen($context);
        $i = 0;
        foreach ($iterable as $key => $o) {
            $oldContext = $context;
            if ($first || $this->separator === null) {
                $context = new PrefixedContext($this->configuration, $context, "");
            } else {
                $context = new PrefixedContext($this->configuration, $context, $this->separator);
            }
            $uniqueNumber = $context->getUniqueNumber();
            $this->applyIndex($context, $key, $uniqueNumber);
            $this->applyItem($context, $o, $uniqueNumber);
            $this->contents->apply(new FilteredDynamicContext($this->configuration, $context, $this->index, $this->item, $uniqueNumber));
            if ($first) {
                $first = !$context->isPrefixApplied();
            }
            $context = $oldContext;
            $i++;
        }
        $this->applyClose($context);
        $context->getBindings()->remove($this->item);
        $context->getBindings()->remove($this->index);
        return true;
    }

    private function applyIndex(DynamicContext $context, $o, int $i): void
    {
        if ($this->index !== null) {
            $context->bind($this->index, $o);
            $context->bind(self::itemizeItem($this->index, $i), $o);
        }
    }

    private function applyItem(DynamicContext $context, $o, int $i): void
    {
        if ($this->item !== null) {
            $context->bind($this->item, $o);
            $context->bind(self::itemizeItem($this->item, $i), $o);
        }
    }

    private function applyOpen(DynamicContext $context): void
    {
        if ($this->open !== null) {
            $context->appendSql($this->open);
        }
    }

    private function applyClose(DynamicContext $context): void
    {
        if ($this->close !== null) {
            $context->appendSql($this->close);
        }
    }

    public static function itemizeItem(string $item, int $i): string
    {
        return self::ITEM_PREFIX . $item . "_" . $i;
    }
}
