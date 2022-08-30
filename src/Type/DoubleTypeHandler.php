<?php

namespace MyBatis\Type;

use Doctrine\DBAL\{
    Result,
    Statement
};
use Doctrine\DBAL\Types\Types;

class DoubleTypeHandler extends BaseTypeHandler
{
    public function setNonNullParameter(Statement $ps, /*string|int*/$i, $parameter, string $type = null): void
    {
        $ps->bindValue($i, $parameter, Types::FLOAT);
    }

    public function getNullableResult(Result $rs, $column)
    {
        return parent::getResult($column);
    }
}
