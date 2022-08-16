<?php

namespace MyBatis\Parsing;

class PropertyParser
{
    private const KEY_PREFIX = "MyBatis\\Parsing\\PropertyParser\\";
    /**
     * The special property key that indicate whether enable a default value on placeholder.
     * <p>
     *   The default value is {@code false} (indicate disable a default value on placeholder)
     *   If you specify the {@code true}, you can specify key and default value on placeholder (e.g. {@code ${db.username:postgres}}).
     * </p>
     */
    public const KEY_ENABLE_DEFAULT_VALUE = self::KEY_PREFIX . "enable-default-value";

    /**
     * The special property key that specify a separator for key and default value on placeholder.
     * <p>
     *   The default separator is {@code ":"}.
     * </p>
     */
    public const KEY_DEFAULT_VALUE_SEPARATOR = self::KEY_PREFIX . "default-value-separator";
    public const ENABLE_DEFAULT_VALUE = false;
    public const DEFAULT_VALUE_SEPARATOR = ":";

    private function __construct()
    {
        // Prevent Instantiation
    }

    public static function parse(string $string, ?array $variables = []): string
    {
        $handler = new VariableTokenHandler($variables);
        $parser = new GenericTokenParser('${', '}', $handler);
        return $parser->parse($string);
    }
}
