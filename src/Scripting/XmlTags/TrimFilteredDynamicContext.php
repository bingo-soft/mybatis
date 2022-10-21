<?php

namespace MyBatis\Scripting\XmlTags;

use MyBatis\Session\Configuration;

class TrimFilteredDynamicContext extends DynamicContext
{
    private $delegate;
    private $prefixApplied;
    private $suffixApplied;
    private $sqlBuffer = "";
    private $prefix;
    private $suffix;

    public function __construct(Configuration $configuration, DynamicContext $delegate, ?string $prefix, ?string $suffix)
    {
        parent::__construct($configuration, null);
        $this->delegate = $delegate;
        $this->prefixApplied = false;
        $this->suffixApplied = false;
        $this->sqlBuffer = $this->sqlBuffer;
        $this->prefix = $prefix;
        $this->suffix = $suffix;
    }

    public function applyAll(): void
    {
        $this->sqlBuffer = trim($this->sqlBuffer);
        $trimmedUppercaseSql = strtoupper($this->sqlBuffer);
        if (strlen($trimmedUppercaseSql) > 0) {
            $this->applyPrefix($this->sqlBuffer, $trimmedUppercaseSql);
            $this->applySuffix($this->sqlBuffer, $trimmedUppercaseSql);
        }
        $this->delegate->appendSql($this->sqlBuffer);
    }

    public function getBindings(): ContextMap
    {
        return $this->delegate->getBindings();
    }

    public function bind(string $name, $value): void
    {
        $this->delegate->bind($name, $value);
    }

    public function getUniqueNumber(): int
    {
        return $this->delegate->getUniqueNumber();
    }

    public function appendSql(string $sql): void
    {
        $this->sqlBuffer->append($sql);
    }

    public function getSql(): string
    {
        return $this->delegate->getSql();
    }

    private function applyPrefix(string &$sql, string $trimmedUppercaseSql): void
    {
        if (!$this->prefixApplied) {
            $this->prefixApplied = true;
            if (!empty($this->prefixesToOverride)) {
                foreach ($this->prefixesToOverride as $toRemove) {
                    if (strpos($trimmedUppercaseSql, $toRemove) === 0) {
                        $sql = substr_replace($sql, "", 0, strlen(trim($toRemove)));
                        break;
                    }
                }
            }
            if ($this->prefix !== null) {
                $sql = " " . $sql;
                $sql = $this->prefix . $sql;
            }
        }
    }

    private function applySuffix(string &$sql, string $trimmedUppercaseSql): void
    {
        if (!$this->suffixApplied) {
            $this->suffixApplied = true;
            if (!empty($this->suffixesToOverride)) {
                foreach ($this->suffixesToOverride as $toRemove) {
                    if (endsWith($trimmedUppercaseSql, $toRemove) || endsWith($trimmedUppercaseSql, trim($toRemove))) {
                        $start = strlen($sql) - strlen(trim($toRemove));
                        $end = strlen($sql) - $start;
                        $sql = substr_replace($sql, "", $start, $end);
                        break;
                    }
                }
            }
            if ($this->suffix !== null) {
                $sql .= " ";
                $sql .= $this->suffix;
            }
        }
    }

    private function endsWith(string $haystack, string $needle): bool
    {
        $length = strlen($needle);
        if (!$length) {
            return true;
        }
        return substr($haystack, -$length) === $needle;
    }
}
