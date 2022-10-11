<?php

namespace MyBatis\Executor\ResultSet;

use MyBatis\Type\TypeHandlerInterface;

class UnMappedColumnAutoMapping
{
    public $column;
    public $property;
    public $typeHandler;
    public $primitive;

    public function __construct(string $column, string $property, TypeHandlerInterface $typeHandler, bool $primitive)
    {
        $this->column = $column;
        $this->property = $property;
        $this->typeHandler = $typeHandler;
        $this->primitive = $primitive;
    }
}
