<?php

namespace MyBatis\Scripting\XmlTags;

use MyBatis\Session\Configuration;

class WhereSqlNode extends TrimSqlNode
{
    private const PREFIX_LIST = ["AND ","OR ","AND\n", "OR\n", "AND\r", "OR\r", "AND\t", "OR\t"];

    public function __construct(Configuration $configuration, SqlNodeInterface $contents)
    {
        parent::__construct($configuration, $contents, "WHERE", self::PREFIX_LIST, null, null);
    }
}
