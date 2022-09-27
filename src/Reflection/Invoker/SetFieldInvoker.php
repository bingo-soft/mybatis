<?php

namespace MyBatis\Reflection\Invoker;

class SetFieldInvoker implements InvokerInterface
{
    private $field;

    public function __construct(\ReflectionProperty $field)
    {
        $this->field = $field;
    }

    public function invoke($target, array $args = [])
    {
        if ($this->field->isPrivate() || $this->field->isProtected()) {
            $this->field->setAccessible(true);
        }
        $this->field->setValue($target, $args[0]);
    }

    public function getType(): ?string
    {
        $type = $this->field->getType();
        if ($type !== null && $type instanceof \ReflectionNamedType) {
            return $type->getName();
        }
    }
}
