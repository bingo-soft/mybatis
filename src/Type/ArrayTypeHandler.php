<?php

namespace MyBatis\Type;

use Doctrine\DBAL\{
    Result,
    Statement
};
use Doctrine\DBAL\Types\Types;

class ArrayTypeHandler extends BaseTypeHandler
{
    public function setNonNullParameter(Statement $ps, /*string|int*/$i, $parameter, string $type = null): void
    {
        if (is_array($parameter)) {
            $ps->bindValue($i, $parameter, Types::ARRAY);
        } else {
            throw new TypeException("ArrayType Handler requires SQL array or PHP array parameter");
        }
    }

    public function getNullableResult(Result $rs, $column)
    {
        return parent::getResult($column);
    }
}
