<?php

namespace MyBatis\Builder;

use MyBatis\Mapping\{
    ParameterMapping,
    ParameterMappingBuilder
};
use MyBatis\Parsing\TokenHandlerInterface;
use MyBatis\Session\Configuration;
use Util\Reflection\{
    MetaClass,
    MetaObject
};

class ParameterMappingTokenHandler extends BaseBuilder implements TokenHandlerInterface
{
    private $parameterMappings = [];
    private $parameterType;
    private $metaParameters;

    public function __construct(Configuration $configuration, string $parameterType, $additionalParameters)
    {
        parent::__construct($configuration);
        $this->parameterType = $parameterType;
        $this->metaParameters = $configuration->newMetaObject($additionalParameters);
    }

    public function getParameterMappings(): array
    {
        return $this->parameterMappings;
    }

    public function handleToken(string $content): string
    {
        $this->parameterMappings[] = $this->buildParameterMapping($content);
        return "?";
    }

    private function buildParameterMapping(string $content): ParameterMapping
    {
        $propertiesMap = $this->parseParameterMapping($content);
        $property = null;
        if (array_key_exists("property", $propertiesMap->getArrayCopy())) {
            $property = $propertiesMap["property"];
        }
        $propertyType = null;
        if ($this->metaParameters->hasGetter($property)) {
            $propertyType = $this->metaParameters->getGetterType($property);
        } elseif ($this->typeHandlerRegistry->hasTypeHandler($this->parameterType)) {
            $propertyType = $this->parameterType;
        } elseif ($property === null || $this->parameterType == 'array') {
            $propertyType = 'object';
        } else {
            $metaClass = new MetaClass($this->parameterType);
            if ($metaClass->hasGetter($property)) {
                $propertyType = $metaClass->getGetterType($property);
            } else {
                $propertyType = 'object';
            }
        }
        $builder = new ParameterMappingBuilder($this->configuration, $property, $propertyType);
        $phpType = $propertyType;
        $typeHandlerAlias = null;
        foreach ($propertiesMap as $name => $value) {
            if ("phpType" == $name) {
                $phpType = $this->resolveClass($value);
                $builder->phpType($phpType);
            } elseif ("dbalType" == $name) {
                $builder->dbalType($this->resolveDbalType($value));
            } elseif ("mode" == $name) {
                $builder->mode($this->resolveParameterMode($value));
            } elseif ("numericScale" == $name) {
                $builder->numericScale(intval($value));
            } elseif ("resultMap" == $name) {
                $builder->resultMapId($value);
            } elseif ("typeHandler" == $name) {
                $typeHandlerAlias = $value;
            } elseif ("dbalTypeName" == $name) {
                $builder->dbalTypeName($value);
            } elseif ("property" == $name) {
                // Do Nothing
            } elseif ("expression" == $name) {
                throw new BuilderException("Expression based parameters are not supported yet");
            } else {
                throw new BuilderException("An invalid property '" . $name . "' was found in mapping #{" . $content . "}.");
            }
        }
        if ($typeHandlerAlias !== null) {
            $builder->typeHandler($this->resolveTypeHandler($phpType, $typeHandlerAlias));
        }
        return $builder->build();
    }

    private function parseParameterMapping(string $content): ParameterExpression
    {
        try {
            return new ParameterExpression($content);
        } catch (BuilderException $ex) {
            throw $ex;
        } catch (\Exception $ex) {
            throw new BuilderException("Parsing error was found in mapping #{" . $content . "}.  Check syntax #{property|(expression), var1=value1, var2=value2, ...} ");
        }
    }
}
