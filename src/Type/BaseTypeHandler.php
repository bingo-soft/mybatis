<?php

namespace MyBatis\Type;

use MyBatis\Executor\Result\ResultMapException;
use Doctrine\DBAL\{
    Result,
    Statement
};

abstract class BaseTypeHandler implements TypeHandlerInterface
{
    public function setParameter(Statement $ps, int $i, $parameter, string $type = null): void
    {
        if ($parameter === null) {
            if ($type === null) {
                throw new TypeException("Type must be specified for all nullable parameters.");
            } else {
                $ps->bindValue($i, $parameter, $type);
            }
        } else {
            $this->setNonNullParameter($ps, $i, $parameter, $type);
        }
    }

    public function getResult(/*Result|array*/$rs, $column)
    {
        $set = [];
        try {
            if (is_int($column)) {
                if (is_array($rs)) {
                    $set = array_values($rs);
                } else {
                    $set = $rs->fetchNumeric();
                }
            } elseif (is_string($column)) {
                if (is_array($rs)) {
                    $set = $rs;
                } else {
                    $set = $rs->fetchAssociative();
                }
            }
            return $set[$column];
        } catch (\Exception $e) {
            throw new ResultMapException("Error attempting to get column $column from result set. Cause: " . $e->getMessage());
        }
    }

    abstract public function setNonNullParameter(Statement $ps, /*string|int*/$i, $parameter, string $type = null): void;

    abstract public function getNullableResult(Result $rs, $column);
}
