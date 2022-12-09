<?php

namespace Tests\Submitted\DynSql;

class CustomUtil
{
    public static function esc(string $s): string
    {
        return str_replace("'", "''", $s);
    }
}
