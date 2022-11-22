<?php

namespace MyBatis\Scripting\XmlTags;

use MyBatis\Parsing\TokenHandlerInterface;
use MyBatis\Scripting\ScriptingException;

class BindingTokenParser implements TokenHandlerInterface
{
    private $context;
    private $injectionFilter;
    private const SIMPLE_TYPES = ['bool', 'boolean', 'int', 'integer', 'float', 'double', 'string', 'array', '\DateTime', '\stdClass'];

    public function __construct(DynamicContext $context, ?string $injectionFilter)
    {
        $this->context = $context;
        $this->injectionFilter = $injectionFilter;
    }

    public function handleToken(string $content): ?string
    {
        $parameter = $this->context->getBindings()->get("_parameter");
        if ($parameter === null) {
            $this->context->getBindings()->put("value", null);
        } elseif (( is_object($parameter) && in_array(get_class($parameter), self::SIMPLE_TYPES) ) || (in_array(gettype($parameter), self::SIMPLE_TYPES))) {
            $this->context->getBindings()->put("value", $parameter);
        }
        //JUEL expects expression to be enclosed in ${}
        $value = JuelCache::getValue('${' . $content . '}', $this->context->getBindings());
        $strValue = strval($value);
        $this->checkInjection($strValue);
        return $strValue;
    }

    private function checkInjection(string $value): void
    {
        if (!empty($this->injectionFilter)) {
            $matches = [];
            preg_match_all($this->injectionFilter, $value, $matches);
            if (empty($matches[0])) {
                throw new ScriptingException("Invalid input. Please conform to regex " . $this->injectionFilter);
            }
        }
    }
}
