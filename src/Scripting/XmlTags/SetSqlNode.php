<?php

namespace MyBatis\Scripting\XmlTags;

use MyBatis\Session\Configuration;

class SetSqlNode extends TrimSqlNode
{
    private const COMMA = [ ',' ];

    public function __construct(Configuration $configuration, SqlNodeInterface $contents)
    {
        parent::__construct($configuration, $contents, "SET", self::COMMA, null, self::COMMA);
    }
}
