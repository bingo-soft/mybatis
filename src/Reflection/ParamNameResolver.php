<?php

namespace MyBatis\Reflection;

use MyBatis\Annotations\Param;
use MyBatis\Binding\ParamMap;
use MyBatis\Session\{
    Configuration,
    ResultHandlerInterface,
    RowBounds
};

class ParamNameResolver
{
    public const GENERIC_NAME_PREFIX = "param";

    private $useActualParamName = false;

    /**
     * <p>
     * The key is the index and the value is the name of the parameter.<br />
     * The name is obtained from {@link Param} if specified. When {@link Param} is not specified,
     * the parameter index is used. Note that this index could be different from the actual index
     * when the method has special parameters (i.e. {@link RowBounds} or {@link ResultHandler}).
     * </p>
     * <ul>
     * <li>aMethod(@Param("M") int a, @Param("N") int b) -&gt; {{0, "M"}, {1, "N"}}</li>
     * <li>aMethod(int a, int b) -&gt; {{0, "0"}, {1, "1"}}</li>
     * <li>aMethod(int a, RowBounds rb, int b) -&gt; {{0, "0"}, {2, "1"}}</li>
     * </ul>
     */
    private $names = [];

    private $hasParamAnnotation = false;

    public function __construct(Configuration $config, \ReflectionMethod $method)
    {
        $this->useActualParamName = $config->isUseActualParamName();

        $paramTypes = [];
        $parameters = $method->getParameters();
        $paramIndex = 0;
        foreach ($parameters as $parameter) {
            $type = $parameter->getType();
            $typeName = null;
            if ($type !== null && $type instanceof \ReflectionNamedType) {
                $typeName = $type->getName();
            }
            if (self::isSpecialParameter($typeName)) {
                $paramIndex += 1;
                continue;
            }

            $name = null;

            $paramAnnos = $parameter->getAttributes(Param::class);

            if (!empty($paramAnnos)) {
                $paramAnno = $paramAnnos[0]->newInstance();
                $name = $paramAnno->value();
            }

            if ($name === null) {
                if ($this->useActualParamName) {
                    $name = $parameter->name;
                } else {
                    $name = count($this->names);
                }
            }
            $this->names[$paramIndex] = $name;
            $paramIndex += 1;
        }
    }

    private static function isSpecialParameter(string $clazz): bool
    {
        return is_a($clazz, RowBounds::class, true) || is_a($clazz, ResultHandlerInterface::class, true);
    }

    /**
     * Returns parameter names referenced by SQL providers.
     *
     * @return the names
     */
    public function getNames(): array
    {
        return $this->names;
    }

    /**
     * <p>
     * A single non-special parameter is returned without a name.
     * Multiple parameters are named using the naming rule.
     * In addition to the default names, this method also adds the generic names (param1, param2,
     * ...).
     * </p>
     *
     * @param args
     *          the args
     * @return the named params
     */
    public function getNamedParams(array $args = [])
    {
        $paramCount = count($this->names);
        if (empty($args) || $paramCount == 0) {
            return [];
        } elseif (!$this->hasParamAnnotation && $paramCount == 1) {
            $value = $args[array_keys($this->names)[0]];
            return self::wrapToMapIfCollection($value, $this->useActualParamName ? $this->names[array_keys($this->names)[0]] : null);
        } else {
            $param = new ParamMap();
            $i = 0;
            foreach ($this->names as $key => $value) {
                $param->put($value, $args[$key]);
                // add generic param names (param1, param2, ...)
                $genericParamName = self::GENERIC_NAME_PREFIX . ($i + 1);
                // ensure not to overwrite parameter named with @Param
                if (!in_array($genericParamName, $this->names)) {
                    $param->put($genericParamName, $args[$key]);
                }
                $i += 1;
            }
            return $param;
        }
    }

    public static function wrapToMapIfCollection($object, ?string $actualParamName)
    {
        if (is_array($object)) {
            $map = new ParamMap();
            $map->put("array", $object);
            if ($actualParamName !== null) {
                $map->put($actualParamName, $object);
            }
            return $map;
        }
        return $object;
    }
}
