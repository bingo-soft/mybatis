<?php

namespace MyBatis\Type;

use Doctrine\DBAL\{
    Result,
    Statement
};
use Doctrine\DBAL\Types\Types;

class EnumTypeHandler extends BaseTypeHandler
{
    private $type;

    public function __construct(?string $type)
    {
        if ($type === null) {
            throw new \Exception("Type argument cannot be null");
        }
        $this->type = $type;
    }

    public function setNonNullParameter(Statement $ps, /*string|int*/$i, $parameter, string $type = null): void
    {
        $ps->bindValue($i, $parameter->name);
    }

    public function getNullableResult(Result $rs, $column)
    {
        $case = parent::getResult($rs, $column);
        $ref = new \ReflectionEnum($this->type);
        return $ref->getCase($case)->getValue();
    }
}
