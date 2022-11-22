<?php

namespace MyBatis\Annotations;

use Attribute;
use MyBatis\Mapping\FetchType;

#[Attribute(Attribute::TARGET_ALL)]
class Many
{
    public function __construct(private string $columnPrefix = "", private string $resultMap = "", private string $select = "", private string $fetchType = FetchType::DEFAULT)
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
