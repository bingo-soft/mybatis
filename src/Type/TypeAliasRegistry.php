<?php

namespace MyBatis\Type;

use Doctrine\DBAL\Types\{
    ArrayType,
    BigIntType,
    //BinaryType,
    BlobType,
    BooleanType,
    DateTimeType,
    DateType,
    DecimalType,
    FloatType,
    IntegerType,
    ObjectType,
    //SmallIntType,
    StringType,
    TextType,
    TypeRegistry,
    Types
};

class TypeAliasRegistry
{
    private $registry;

    public function __construct()
    {
        $this->registry = new TypeRegistry();
        $this->registry->register(Types::ARRAY, new ArrayType());
        $this->registry->register(Types::BIGINT, new BigIntType());
        //$this->registry->register(Types::BINARY, new BinaryType());
        $this->registry->register(Types::BLOB, new BlobType());
        $this->registry->register(Types::BOOLEAN, new BooleanType());
        $this->registry->register(Types::DATE_MUTABLE, new DateType());
        $this->registry->register(Types::DATETIME_MUTABLE, new DateTimeType());
        $this->registry->register(Types::DECIMAL, new DecimalType());
        $this->registry->register(Types::FLOAT, new FloatType());
        $this->registry->register(Types::INTEGER, new IntegerType());
        $this->registry->register(Types::OBJECT, new ObjectType());
        //$this->registry->register(Types::SMALLINT, new SmallIntType());
        $this->registry->register(Types::STRING, new StringType());
        $this->registry->register(Types::TEXT, new TextType());
    }

    public function resolveAlias(string $name)
    {
        return $this->registry->get($name);
    }

    public function getTypeAliases(): array
    {
        return $this->registry->getMap();
    }
}
