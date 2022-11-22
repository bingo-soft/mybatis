<?php

namespace MyBatis\Type;

use Doctrine\DBAL\Types\Types;

class DbalType
{
    private static $codeLookup = [];

    public $typeCode;

    private static function ensureInit(): void
    {
        if (empty(self::$codeLookup)) {
            $types = (new \ReflectionClass(Types::class))->getConstants();
            foreach ($types as $key => $value) {
                self::$codeLookup[$key] = new DbalType($value);
            }
            self::$codeLookup['VARCHAR'] = new DbalType(Types::STRING);
            self::$codeLookup['TIMESTAMP'] = new DbalType('timestamp');
            self::$codeLookup['OTHER'] = new DbalType('other');
            self::$codeLookup['UNDEFINED'] = new DbalType('undefined');
        }
    }

    private function __construct($code)
    {
        $this->typeCode = $code;
    }

    public static function forCode($code): ?DbalType
    {
        self::ensureInit();
        if (array_key_exists(strtoupper($code), self::$codeLookup)) {
            return self::$codeLookup[strtoupper($code)];
        }
        return null;
    }

    public function __toString()
    {
        return $this->typeCode;
    }
}
