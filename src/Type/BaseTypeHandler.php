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
        $this->setNonNullParameter($ps, $i, $parameter, $type);
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
            if (is_int($column)) {
                return $set[$column];
            } elseif (is_string($column)) {
                if (isset($set[$column])) {
                    return $set[$column];
                }
                if (isset($set[strtolower($column)])) {
                    return $set[strtolower($column)];
                }
                if (isset($set[strtoupper($column)])) {
                    return $set[strtoupper($column)];
                }
            }
        } catch (\Throwable $e) {
            throw new ResultMapException("Error attempting to get column $column from result set. Cause: " . $e->getMessage());
        }
    }

    abstract public function setNonNullParameter(Statement $ps, /*string|int*/$i, $parameter, string $type = null): void;

    abstract public function getNullableResult(Result $rs, $column);

    protected function getParameterValue(/*string|int*/$i, $parameter)
    {
        return $parameter;
    }
}
