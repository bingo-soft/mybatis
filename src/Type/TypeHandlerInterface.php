<?php

namespace MyBatis\Type;

use Doctrine\DBAL\{
    Result,
    Statement
};

interface TypeHandlerInterface
{
    public function setParameter(Statement $ps, int $i, $parameter, string $type = null): void;

    public function getResult(/*Result|array*/$rs, $column);
}
