<?php

namespace MyBatis\Builder\Annotation;

use MyBatis\Annotations\Lang;
use MyBatis\Builder\BuilderException;
use MyBatis\Mapping\{
    BoundSql,
    SqlSourceInterface
};
use MyBatis\Reflection\ParamNameResolver;
use MyBatis\Session\Configuration;

class ProviderSqlSource implements SqlSourceInterface
{
    private $configuration;
    private $providerType;
    private $providerTypeRef;
    private $languageDriver;
    private $mapperMethod;
    private $providerMethod;
    private $providerMethodArgumentNames;
    private $providerMethodParameters;
    private $providerMethodParameterTypes = [];
    private $providerContext;
    private $providerContextIndex;

    public function __construct(Configuration $configuration, $provider, ?string $mapperType = null, ?\ReflectionMethod $mapperMethod = null)
    {
        $candidateProviderMethodName = null;
        $candidateProviderMethod = null;
        try {
            $this->configuration = $configuration;
            $this->mapperMethod = $mapperMethod;
            $langs = $mapperMethod == null ? null : $mapperMethod->getAttributes(Lang::class);
            $lang = !empty($langs) ? $langs[0] : null;
            $this->languageDriver = $configuration->getLanguageDriver($lang === null ? null : $lang->value());
            $this->providerType = $this->getProviderType($configuration, $provider, $mapperMethod);
            $this->providerTypeRef = new \ReflectionClass($this->providerType);
            $candidateProviderMethodName = $provider->method();

            if (empty($candidateProviderMethodName) && is_a($this->providerType, ProviderMethodResolver::class, true)) {
                $providerClass = $this->providerType;
                $candidateProviderMethod = (new $providerClass())->resolveMethod(new ProviderContext($mapperType, $mapperMethod, $configuration->getDatabaseId()));
            }
            if ($candidateProviderMethod === null) {
                $candidateProviderMethodName = empty($candidateProviderMethodName) ? "provideSql" : $candidateProviderMethodName;
                foreach ($this->providerTypeRef->getMethods() as $m) {
                    if ($candidateProviderMethodName == $m->name && $m->getReturnType() == 'string') {
                        if ($candidateProviderMethod !== null) {
                            throw new BuilderException("Error creating SqlSource for SqlProvider");
                        }
                        $candidateProviderMethod = $m;
                    }
                }
            }
        } catch (BuilderException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new BuilderException("Error creating SqlSource for SqlProvider.  Cause: " . $e->getMessage());
        }
        if ($candidateProviderMethod === null) {
            throw new BuilderException("Error creating SqlSource for SqlProvider.");
        }
        $this->providerMethod = $candidateProviderMethod;
        $this->providerMethodArgumentNames = (new ParamNameResolver($configuration, $this->providerMethod))->getNames();
        $this->providerMethodParameters = $this->providerMethod->getParameters();

        $candidateProviderContext = null;
        $candidateProviderContextIndex = null;
        for ($i = 0; $i < count($this->providerMethodParameters); $i += 1) {
            $parameterRefType = $this->providerMethodParameter[$i]->getType();
            $parameterType = null;
            if ($parameterRefType instanceof \ReflectionNamedType) {
                $parameterType = $parameterRefType->getName();
            }
            $this->providerMethodParameterTypes[$i] = $parameterType;
            if ($parameterType == ProviderContext::class) {
                if ($candidateProviderContext !== null) {
                    throw new BuilderException("Error creating SqlSource for SqlProvider. ProviderContext found multiple in SqlProvider method");
                }
                $candidateProviderContext = new ProviderContext($mapperType, $mapperMethod, $configuration->getDatabaseId());
                $candidateProviderContextIndex = $i;
            }
        }
        $this->providerContext = $candidateProviderContext;
        $this->providerContextIndex = $candidateProviderContextIndex;
    }

    public function getBoundSql($parameterObject): BoundSql
    {
        $sqlSource = $this->createSqlSource($parameterObject);
        return $sqlSource->getBoundSql($parameterObject);
    }

    private function createSqlSource($parameterObject = null): SqlSourceInterface
    {
        /*try {*/
            $sql = "";
            if (is_array($parameterObject)) {
                $sql = $this->invokeProviderMethod($this->extractProviderMethodArguments($parameterObject, $this->providerMethodArgumentNames));
            } elseif (count($this->providerMethodParameterTypes) == 0) {
                $sql = $this->invokeProviderMethod();
            } elseif (count($this->providerMethodParameterTypes) == 1) {
                if ($this->providerContext === null) {
                    $sql = $this->invokeProviderMethod($parameterObject);
                } else {
                    $sql = $this->invokeProviderMethod($providerContext);
                }
            } elseif (count($providerMethodParameterTypes) == 2) {
                $sql = $this->invokeProviderMethod($this->extractProviderMethodArguments($parameterObject));
            } else {
                throw new BuilderException("Cannot invoke SqlProvider method");
            }
            $parameterType = $parameterObject == null ? "object" : (is_object($parameterObject) ? get_class($parameterObject) : gettype($parameterObject));
            return $this->languageDriver->createSqlSource($this->configuration, $sql, $parameterType);
        /*} catch (BuilderException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new BuilderException("Error invoking SqlProvider method. Cause: " . $e->getMessage());
        }*/
    }

    private function extractProviderMethodArguments($parameterObject, array $argumentNames = []): array
    {
        if (is_array($parameterObject) && !empty($argumentNames)) {
            $args = [];
            for ($i = 0; $i < count($argumentNames); $i += 1) {
                if ($this->providerContextIndex !== null && $this->providerContextIndex == $i) {
                    $args[$i] = $providerContext;
                } else {
                    if (array_key_exists($argumentNames[$i], $parameterObject)) {
                        $args[$i] = $parameterObject($argumentNames[$i]);
                    }
                }
            }
            return $args;
        } else {
            if ($this->providerContext !== null) {
                $args = [];
                $args[$this->providerContextIndex == 0 ? 1 : 0] = $parameterObject;
                $args[$this->providerContextIndex] = $providerContext;
                return $args;
            }
            return [ $parameterObject ];
        }
    }

    private function invokeProviderMethod(...$args): ?string
    {
        $targetObject = null;
        if (!$this->providerMethod->isStatic()) {
            $providerClass = $this->providerType;
            $targetObject = new $providerClass();
        }
        //@TODO. Check it!
        $sql = $this->providerMethod->invoke($targetObject, ...$args);
        return $sql !== null ? $sql : null;
    }

    private function getProviderType(Configuration $configuration, $providerAnnotation, \ReflectionMethod $mapperMethod): string
    {
        $type = $providerAnnotation->type();
        $value = $providerAnnotation->value();
        if ($value == 'void' && $type == 'void') {
            if ($configuration->getDefaultSqlProviderType() !== null) {
                return $configuration->getDefaultSqlProviderType();
            }
            throw new BuilderException("Please specify either 'value' or 'type' attribute");
        }
        if ($value != 'void' && $type != 'void' && $value != $type) {
            throw new BuilderException("Cannot specify different class on 'value' and 'type' attribute");
        }
        return $value == 'void' ? $type : $value;
    }
}
