<?php

namespace MyBatis\Type;

use Doctrine\DBAL\{
    Result,
    Statement
};
use Doctrine\DBAL\Types\Types;

class JsonTypeHandler extends BaseTypeHandler
{
    public function setNonNullParameter(Statement $ps, /*string|int*/$i, $parameter, string $type = null): void
    {
        if (is_array($parameter)) {
            $ps->bindValue($i, $parameter, Types::JSON);
        } else {
            throw new TypeException("JsonType Handler requires json parameter");
        }
    }

    public function getNullableResult(Result $rs, $column)
    {
        return parent::getResult($rs, $column);
    }
}
