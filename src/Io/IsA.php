<?php

namespace MyBatis\Io;

class IsA implements TestInterface
{
    private $parent;

    public function __construct(string $parentType)
    {
        $this->parent = $parentType;
    }

    /** Returns true if type is assignable to the parent type supplied in the constructor. */
    public function matches($subtype): bool
    {
        return $subtype !== null && ($this->parent == 'object' || is_a($subtype, $this->parent, true));
    }

    public function __toString()
    {
        return "is assignable to " . $this->parent;
    }
}
