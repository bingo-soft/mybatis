<?php

namespace MyBatis\Parsing;

class VariableTokenHandler implements TokenHandlerInterface
{
    private $variables;
    private $enableDefaultValue;
    private $defaultValueSeparator;

    public function __construct(?array $variables = [])
    {
        $this->variables = $variables;
        $this->enableDefaultValue = Boolean::parseBoolean($this->getPropertyValue(PropertyParser::KEY_ENABLE_DEFAULT_VALUE, PropertyParser::ENABLE_DEFAULT_VALUE));
        $this->defaultValueSeparator = $this->getPropertyValue(PropertyParser::KEY_DEFAULT_VALUE_SEPARATOR, PropertyParser::DEFAULT_VALUE_SEPARATOR);
    }

    private function getPropertyValue(string $key, string $defaultValue): string
    {
        if (empty($this->variables)) {
            return $defaultValue;
        } else {
            if (array_key_exists($key, $this->variables)) {
                return $this->variables[$key];
            }
            return $defaultValue;
        }
    }

    public function handleToken(string $content): ?string
    {
        if (!empty($this->variables)) {
            $key = $content;
            if ($this->enableDefaultValue) {
                $separatorIndex = strpos($content, $this->defaultValueSeparator);
                $defaultValue = null;
                if ($separatorIndex !== false) {
                    $key = substr($content, 0, $separatorIndex);
                    $defaultValue = substr($content, $separatorIndex + strlen($this->defaultValueSeparator));
                }
                if ($defaultValue !== null) {
                    return $this->getPropertyValue($key, $defaultValue);
                }
            }
            if (array_key_exists($key, $this->variables)) {
                return $this->variables[$key];
            }
        }
        return '${' . $content . '}';
    }
}
