<?php

namespace Tests\Builder;

use Doctrine\DBAL\{
    Result,
    Statement
};
use MyBatis\Type\{
    DbalType,
    TypeHandlerInterface
};

class CustomStringTypeHandler implements TypeHandlerInterface
{
    public function setParameter(Statement $ps, int $i, $parameter, string $type = null): void
    {
        $ps->bindValue($i, $parameter);
    }

    public function getResult(/*Result|array*/$rs, $column)
    {
        $res = $rs->fetchAssociative();
        if (array_key_exists($column, $res)) {
            return $res[$column];
        }
        return null;
    }
}
