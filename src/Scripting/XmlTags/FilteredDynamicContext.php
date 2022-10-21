<?php

namespace MyBatis\Scripting\XmlTags;

use MyBatis\Parsing\GenericTokenParser;
use MyBatis\Session\Configuration;
use MyBatis\Parsing\TokenHandlerInterface;

class FilteredDynamicContext extends DynamicContext
{
    private $delegate;
    private $index;
    private $itemIndex;
    private $item;

    public function __construct(Configuration $configuration, DynamicContext $delegate, string $itemIndex, string $item, int $i)
    {
        parent::__construct($configuration, null);
        $this->delegate = $delegate;
        $this->index = $i;
        $this->itemIndex = $itemIndex;
        $this->item = $item;
    }

    public function getBindings(): ContextMap
    {
        return $this->delegate->getBindings();
    }

    public function bind(string $name, $value): void
    {
        $this->delegate->bind($name, $value);
    }

    public function getSql(): string
    {
        return $this->delegate->getSql();
    }

    public function appendSql(?string $sql): void
    {
        $item = $this->item;
        $index = $this->index;
        $itemIndex = $this->itemIndex;
        $parser = new GenericTokenParser("#{", "}", new class ($item, $index, $itemIndex) implements TokenHandlerInterface {
            private $item;
            private $index;
            private $itemIndex;

            public function __construct($item, $index, $itemIndex)
            {
                $this->item = $item;
                $this->index = $index;
                $this->itemIndex = $itemIndex;
            }

            public function handleToken(string $content): ?string
            {
                $newContent = preg_replace("/^\s*" . $this->item . "(?![^.,:\s])/", ForEachSqlNode::itemizeItem($this->item, $this->index), $content, 1);
                if ($this->itemIndex != null && $newContent == $content) {
                    $newContent = preg_replace("/^\s*" . $this->itemIndex . "(?![^.,:\s])/", ForEachSqlNode::itemizeItem($this->itemIndex, $this->index), $content, 1);
                }
                return "#{" . $newContent . "}";
            }
        });

        $this->delegate->appendSql($parser->parse($sql));
    }

    public function getUniqueNumber(): int
    {
        return $this->delegate->getUniqueNumber();
    }
}
