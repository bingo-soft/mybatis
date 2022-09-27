<?php

namespace MyBatis\Reflection;

class SystemMetaObject
{
    private static $NULL_META_OBJECT;

    public static function forObject($object): MetaObject
    {
        return new MetaObject($object);
    }

    public static function nullMetaObject(): MetaObject
    {
        if (self::$NULL_META_OBJECT === null) {
            self::$NULL_META_OBJECT = new MetaObject(new NullObject());
        }
        return self::$NULL_META_OBJECT;
    }
}
