<?php

namespace MyBatis\Type;

use Doctrine\DBAL\Result;

class TypeAliasRegistry
{
    private $typeAliases = [];

    public function __construct()
    {
        $this->registerAlias("string", "string");
        $this->registerAlias("char", "string");
        $this->registerAlias("character", "string");
        $this->registerAlias("long", "integer");
        $this->registerAlias("short", "integer");
        $this->registerAlias("int", "integer");
        $this->registerAlias("integer", "integer");
        $this->registerAlias("double", "float");
        $this->registerAlias("float", "float");
        $this->registerAlias("boolean", "boolean");

        $this->registerAlias("char[]", "array");
        $this->registerAlias("character[]", "array");
        $this->registerAlias("long[]", "array");
        $this->registerAlias("short[]", "array");
        $this->registerAlias("int[]", "array");
        $this->registerAlias("integer[]", "array");
        $this->registerAlias("double[]", "array");
        $this->registerAlias("float[]", "array");
        $this->registerAlias("boolean[]", "array");

        $this->registerAlias("_char", "string");
        $this->registerAlias("_character", "string");
        $this->registerAlias("_long", "integer");
        $this->registerAlias("_short", "integer");
        $this->registerAlias("_int", "integer");
        $this->registerAlias("_integer", "integer");
        $this->registerAlias("_double", "float");
        $this->registerAlias("_float", "float");
        $this->registerAlias("_boolean", "boolean");

        $this->registerAlias("_char[]", "array");
        $this->registerAlias("_character[]", "array");
        $this->registerAlias("_long[]", "array");
        $this->registerAlias("_short[]", "array");
        $this->registerAlias("_int[]", "array");
        $this->registerAlias("_integer[]", "array");
        $this->registerAlias("_double[]", "array");
        $this->registerAlias("_float[]", "array");
        $this->registerAlias("_boolean[]", "array");

        $this->registerAlias("date", "DateTime");
        $this->registerAlias("decimal", "decimal");
        $this->registerAlias("object", "object");

        $this->registerAlias("date[]", "array");
        $this->registerAlias("object[]", "array");

        $this->registerAlias("map", "array");
        $this->registerAlias("hashmap", "array");
        $this->registerAlias("list", "array");
        $this->registerAlias("arraylist", "array");
        $this->registerAlias("collection", "array");
        $this->registerAlias("iterator", "array");

        $this->registerAlias("ResultSet", Result::class);
    }

    public function resolveAlias(?string $string): ?string
    {
        try {
            if ($string == null) {
                return null;
            }
            $key = strtolower($string);
            $value = null;
            if (array_key_exists($key, $this->typeAliases)) {
                $value = $this->typeAliases[$key];
            } else {
                $value = class_exists($string) ? $string : null;
            }
            return $value;
        } catch (\Exception $e) {
            throw new TypeException("Could not resolve type alias '" . $string . "'.  Cause: " . $e->getMessage());
        }
    }

    public function registerAliases(string $packageName, string $superType = 'object'): void
    {
        $resolverUtil = new ResolverUtil();
        $resolverUtil->find(new IsA($superType), $packageName);
        $typeSet = $resolverUtil->getClasses();
        foreach ($typeSet as $type) {
            if (class_exists($type)) {
                $ref = new \ReflectionClass($type);
                if (!$ref->isInterface()) {
                    $this->registerAlias($type);
                }
            }
        }
    }

    public function registerAlias(string $aliasOrType, string $value = null): void
    {
        if ($value === null && class_exists($aliasOrType)) {
            $ref = new \ReflectionClass($aliasOrType);
            $value = $aliasOrType;
            $aliasOrType = $ref->getShortName();
        }
        // issue #748
        $key = strtolower($aliasOrType);
        if (array_key_exists($key, $this->typeAliases) && $this->typeAliases[$key] !== null && $this->typeAliases[$key] != $value) {
            throw new TypeException("The alias '" . $aliasOrType . "' is already mapped to the value '" . $this->typeAliases[$key] . "'.");
        }
        $this->typeAliases[$key] = $value;
    }

    /**
     * Gets the type aliases.
     *
     * @return the type aliases
     */
    public function getTypeAliases(): array
    {
        return $this->typeAliases;
    }
}
