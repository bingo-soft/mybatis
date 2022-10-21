<?php

namespace MyBatis\Scripting\XmlTags;

use MyBatis\Session\Configuration;

class PrefixedContext extends DynamicContext
{
    private $delegate;
    private $prefix;
    private $prefixApplied;

    public function __construct(Configuration $configuration, DynamicContext $delegate, string $prefix)
    {
        parent::__construct($configuration, null);
        $this->delegate = $delegate;
        $this->prefix = $prefix;
        $this->prefixApplied = false;
    }

    public function isPrefixApplied(): bool
    {
        return $this->prefixApplied;
    }

    public function getBindings(): ContextMap
    {
        return $this->delegate->getBindings();
    }

    public function bind(string $name, $value): void
    {
        $this->delegate->bind($name, $value);
    }

    public function appendSql(?string $sql): void
    {
        if (!$this->prefixApplied && !empty($sql)) {
            $this->delegate->appendSql($this->prefix);
            $this->prefixApplied = true;
        }
        $this->delegate->appendSql($sql);
    }

    public function getSql(): string
    {
        return $this->delegate->getSql();
    }

    public function getUniqueNumber(): int
    {
        return $this->delegate->getUniqueNumber();
    }
}
