<?php

namespace MyBatis\Type;

use Doctrine\DBAL\Types\Types;

class DbalType
{
    private static $JSON;
    private static $BIGINT;
    private static $BLOB;
    private static $BOOLEAN;
    private static $DATE;
    private static $DATETIME;
    private static $FLOAT;
    private static $INTEGER;
    private static $STRING;
    private static $TEXT;

    private static $codeLookup = [];

    public $typeCode;

    private static function ensureInit(): void
    {
        if (self::$JSON === null) {
            self::$codeLookup[Types::BIGINT] = new DbalType(Types::BIGINT);
            self::$codeLookup[Types::BLOB] = new DbalType(Types::BLOB);
            self::$codeLookup[Types::BOOLEAN] = new DbalType(Types::BOOLEAN);
            self::$codeLookup[Types::DATE_MUTABLE] = new DbalType(Types::DATE_MUTABLE);
            self::$codeLookup[Types::DATETIME_MUTABLE] = new DbalType(Types::DATETIME_MUTABLE);
            self::$codeLookup[Types::FLOAT] = new DbalType(Types::FLOAT);
            self::$codeLookup[Types::INTEGER] = new DbalType(Types::INTEGER);
            self::$codeLookup[Types::DECIMAL] = new DbalType(Types::DECIMAL);
            self::$codeLookup[Types::JSON] = new DbalType(Types::JSON);
            self::$codeLookup[Types::STRING] = new DbalType(Types::STRING);
            self::$codeLookup[Types::TEXT] = new DbalType(Types::TEXT);
            self::$codeLookup['timestamp'] = new DbalType('timestamp');
        }
    }

    private function __construct($code)
    {
        $this->typeCode = $code;
    }

    public static function forCode($code): ?DbalType
    {
        self::ensureInit();
        if (array_key_exists($code, self::$codeLookup)) {
            return self::$codeLookup[$code];
        }
        return null;
    }
}
