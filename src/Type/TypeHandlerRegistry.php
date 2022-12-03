<?php

namespace MyBatis\Type;

use Doctrine\DBAL\Types\Types;
use MyBatis\Binding\ParamMap;
use MyBatis\Io\{
    IsA,
    ResolverUtil
};
use MyBatis\Session\Configuration;

class TypeHandlerRegistry
{
    private $unknownTypeHandler;
    private $allTypeHandlersMap = [];
    private $defaultEnumTypeHandler;

    /**
     * The constructor that pass the MyBatis configuration.
     *
     * @param configuration a MyBatis configuration
     */
    public function __construct(?Configuration $configuration = null)
    {
        $this->unknownTypeHandler = new UnknownTypeHandler($configuration);
        $this->defaultEnumTypeHandler = EnumTypeHandler::class;

        $this->register("bool", new BooleanTypeHandler());
        $this->register("boolean", new BooleanTypeHandler());
        $this->register(DbalType::forCode(Types::BOOLEAN), new BooleanTypeHandler());

        $this->register("int", new IntegerTypeHandler());
        $this->register("integer", new IntegerTypeHandler());
        $this->register(DbalType::forCode(Types::INTEGER), new IntegerTypeHandler());

        $this->register("float", new FloatTypeHandler());
        $this->register(DbalType::forCode(Types::FLOAT), new FloatTypeHandler());

        $this->register("string", new StringTypeHandler());
        $this->register("char", new StringTypeHandler());
        $this->register("varchar", new StringTypeHandler());
        $this->register(DbalType::forCode(Types::STRING), new StringTypeHandler());

        $this->register("json", new JsonTypeHandler());
        $this->register(DbalType::forCode(Types::JSON), new JsonTypeHandler());

        $this->register("bigint", new BigIntegerTypeHandler());
        $this->register("biginteger", new BigIntegerTypeHandler());
        $this->register(DbalType::forCode(Types::BIGINT), new BigIntegerTypeHandler());

        $this->register("decimal", new DecimalTypeHandler());
        $this->register("numeric", new DecimalTypeHandler());
        $this->register(DbalType::forCode(Types::DECIMAL), new DecimalTypeHandler());

        $this->register("blob", new BlobTypeHandler());
        $this->register(DbalType::forCode(Types::BLOB), new BlobTypeHandler());

        $this->register("object", $this->unknownTypeHandler);
        $this->register("other", $this->unknownTypeHandler);

        $this->register("date", new DateTypeHandler());
        $this->register(DbalType::forCode(Types::DATE_MUTABLE), new DateTypeHandler());
        $this->register("DateTime", new DateTypeHandler());
        $this->register(DbalType::forCode(Types::DATETIME_MUTABLE), new DatetimeTypeHandler());
    }

    public function setDefaultEnumTypeHandler(TypeHandlerInterface $typeHandler): void
    {
        $this->defaultEnumTypeHandler = $typeHandler;
    }

    public function hasTypeHandler($type): bool
    {
        return $this->getTypeHandler($type) !== null;
    }

    public function getTypeHandler($type): ?TypeHandlerInterface
    {
        if (ParamMap::class == $type) {
            return null;
        }
        foreach ($this->allTypeHandlersMap as $pair) {
            if ($pair[0] == $type) {
                return $pair[1];
            }
        }
        if (is_string($type) && class_exists($type) && $type !== DbalType::class) {
            //try to get by super type
            foreach ($this->allTypeHandlersMap as $pair) {
                if (is_string($pair[0]) && class_exists($pair[0]) && (new IsA($pair[0]))->matches($type)) {
                    return $pair[1];
                }
            }
            //if handler not defined for enum
            if (is_a($type, \UnitEnum::class, true)) {
                $handler = $this->getInstance($type, $this->defaultEnumTypeHandler);
                $this->register($type, $handler);
                return $handler;
            }
        }
        return null;
    }

    public function getMappingTypeHandler($type): ?TypeHandlerInterface
    {
        return $this->getTypeHandler($type);
    }

    public function getUnknownTypeHandler(): ?TypeHandlerInterface
    {
        return $this->unknownTypeHandler;
    }

    public function register($type, /*TypeHandlerInterface|string*/$handler): void
    {
        if ($handler instanceof TypeHandlerInterface) {
            $exists = false;
            foreach ($this->allTypeHandlersMap as $key => $pair) {
                if ($pair[0] == $type) {
                    $this->allTypeHandlersMap[$key] = [$type, $handler];
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $this->allTypeHandlersMap[] = [$type, $handler];
            }
        } elseif (is_string($handler) && class_exists($handler)) {
            $handlerImpl = new $handler();
            $exists = false;
            foreach ($this->allTypeHandlersMap as $key => $pair) {
                if ($pair[0] == $type) {
                    $this->allTypeHandlersMap[$key] = [$type, $handlerImpl];
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $this->allTypeHandlersMap[] = [$type, $handlerImpl];
            }
        }
    }

    // Construct a handler (used also from Builders)
    public function getInstance(?string $phpTypeClass, string $typeHandlerClass): TypeHandlerInterface
    {
        if ($phpTypeClass !== null) {
            return new $typeHandlerClass($phpTypeClass);
        } else {
            return new $typeHandlerClass();
        }
    }

    /**
     * Gets the type handlers.
     *
     * @return the type handlers
     */
    public function getTypeHandlers(): array
    {
        return array_values($this->allTypeHandlersMap);
    }
}
