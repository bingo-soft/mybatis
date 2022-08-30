<?php

namespace MyBatis\Mapping;

class ResultSetType
{
    public const TYPE_FORWARD_ONLY = 1003;

    public const TYPE_SCROLL_INSENSITIVE = 1004;

    public const TYPE_SCROLL_SENSITIVE = 1005;

    private $value;

    private function __construct(int $type)
    {
        $this->value = $value;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    private static $DEFAULT;

    public static function default(): ResultSetType
    {
        if (self::$DEFAULT === null) {
            self::$DEFAULT = new ResultSetType(-1);
        }
        return self::$DEFAULT;
    }

    private static $FORWARD_ONLY;

    public static function forwardOnly(): ResultSetType
    {
        if (self::$FORWARD_ONLY === null) {
            self::$FORWARD_ONLY = new ResultSetType(self::TYPE_FORWARD_ONLY);
        }
        return self::$FORWARD_ONLY;
    }

    private static $INSENSITIVE;

    public static function scrollInsensitive(): ResultSetType
    {
        if (self::$INSENSITIVE === null) {
            self::$INSENSITIVE = new ResultSetType(self::TYPE_SCROLL_INSENSITIVE);
        }
        return self::$INSENSITIVE;
    }

    private static $SENSITIVE;

    public static function scrollSensitive(): ResultSetType
    {
        if (self::$SENSITIVE === null) {
            self::$SENSITIVE = new ResultSetType(self::TYPE_SCROLL_SENSITIVE);
        }
        return self::$SENSITIVE;
    }
}
