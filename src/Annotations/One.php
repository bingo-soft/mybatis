<?php

namespace MyBatis\Annotations;

use MyBatis\Mapping\FatchType;

#[Attribute(Attribute::TARGET_ALL)]
class One
{
    public function __construct(private string $columnPrefix = "", private string $resultMap = "", private string $select = "", private string $fetchType = FatchType::DEFAULT)
    {
    }

    public function columnPrefix(): string
    {
        return $this->columnPrefix;
    }

    public function resultMap(): string
    {
        return $this->resultMap;
    }

    public function select(): string
    {
        return $this->select;
    }

    public function fetchType(): string
    {
        return $this->fetchType;
    }
}
