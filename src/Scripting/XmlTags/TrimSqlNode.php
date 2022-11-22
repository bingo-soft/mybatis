<?php

namespace MyBatis\Scripting\XmlTags;

use MyBatis\Session\Configuration;

class TrimSqlNode implements SqlNodeInterface
{
    private $contents;
    private $prefix;
    private $suffix;
    private $prefixesToOverride = [];
    private $suffixesToOverride = [];
    private $configuration;

    public function __construct(Configuration $configuration, SqlNodeInterface $contents, string $prefix, /*string|array*/$prefixesToOverride, ?string $suffix, /*string|array*/$suffixesToOverride)
    {
        $this->contents = $contents;
        $this->prefix = $prefix;
        $this->prefixesToOverride = is_string($prefixesToOverride) ? self::parseOverrides($prefixesToOverride) : $prefixesToOverride;
        $this->suffix = $suffix;
        $this->suffixesToOverride = is_string($suffixesToOverride) ? self::parseOverrides($suffixesToOverride) : $suffixesToOverride;
        $this->configuration = $configuration;
    }

    public function apply(DynamicContext $context): bool
    {
        $filteredDynamicContext = new TrimFilteredDynamicContext($this->configuration, $context, $this->prefix, $this->suffix, $this->prefixesToOverride, $this->suffixesToOverride);
        $result = $this->contents->apply($filteredDynamicContext);
        $filteredDynamicContext->applyAll();
        return $result;
    }

    private static function parseOverrides(?string $overrides): array
    {
        if (!empty($overrides)) {
            $list = array_map(
                function ($var) {
                    return strtoupper($var);
                },
                explode('|', $overrides)
            );
            return $list;
        }
        return [];
    }
}
