<?php

namespace MyBatis\Annotations;

class FlushCachePolicy
{
    /** <code>false</code> for select statement; <code>true</code> for insert/update/delete statement. */
    public const DEFAULT = 'DEFAULT';
    /** Flushes cache regardless of the statement type. */
    public const TRUE = 'true';
    /** Does not flush cache regardless of the statement type. */
    public const FALSE = 'false';
}
