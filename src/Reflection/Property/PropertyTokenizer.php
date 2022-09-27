<?php

namespace MyBatis\Reflection\Property;

class PropertyTokenizer extends \ArrayIterator
{
    private $name;
    private $indexedName;
    private $index;
    private $children;

    public function __construct(string $fullname)
    {
        $delim = strpos($fullname, '.');
        if ($delim !== false) {
            $this->name = substr($fullname, 0, $delim);
            $this->children = substr($fullname, $delim + 1);
        } else {
            $this->name = $fullname;
            $this->children = null;
        }
        $this->indexedName = $this->name;
        $delim = strpos($this->name, '[');
        if ($delim !== false) {
            $this->index = substr($this->name, $delim + 1, strlen($this->name) - 1 - ($delim + 1));
            $this->name = substr($this->name, 0, $delim);
        }
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getIndex(): ?string
    {
        return $this->index;
    }

    public function getIndexedName(): ?string
    {
        return $this->indexedName;
    }

    public function getChildren(): ?string
    {
        return $this->children;
    }

    public function valid(): bool
    {
        return $this->children !== null;
    }

    public function current()
    {
        return $this;
    }

    public function next()
    {
        return new PropertyTokenizer($this->children);
    }
}
