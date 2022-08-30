<?php

namespace MyBatis\Mapping;

class SqlCommandType
{
    public const UNKNOWN = 'unknown';
    public const INSERT = 'insert';
    public const UPDATE = 'update';
    public const DELETE = 'delete';
    public const SELECT = 'select';
    public const FLUSH = 'flush';
}
