<?php

namespace MyBatis\Mapping;

interface SqlSourceInterface
{
    public function getBoundSql($parameterObject): BoundSql;
}
