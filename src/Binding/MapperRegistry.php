<?php

namespace MyBatis\Binding;

use MyBatis\Builder\Annotation\MapperAnnotationBuilder;
use MyBatis\Io\{
    IsA,
    ResolverUtil
};
use MyBatis\Session\{
    Configuration,
    SqlSessionInterface
};

class MapperRegistry
{
    private $config;
    private $knownMappers = [];

    public function __construct(Configuration $config)
    {
        $this->config = $config;
    }

    public function getMapper(string $type, SqlSessionInterface $sqlSession)
    {
        $mapperProxyFactory = null;
        if (array_key_exists($type, $this->knownMappers)) {
            $mapperProxyFactory = $this->knownMappers[$type];
        }
        if ($mapperProxyFactory === null) {
            throw new BindingException("Type " . $type . " is not known to the MapperRegistry.");
        }
        try {
            return $mapperProxyFactory->newInstance($sqlSession);
        } catch (\Throwable $e) {
            throw new BindingException("Error getting mapper instance. Cause: " . $e->getMessage());
        }
    }

    public function hasMapper(string $type): bool
    {
        return array_key_exists($type, $this->knownMappers);
    }

    public function addMapper(string $type): void
    {
        $refType = null;
        if (!class_exists($type)) {
            try {
                $refType = new \ReflectionClass($type);
            } catch (\Throwable $e) {
                //ignore
                $refType = null;
            }
        } else {
            $refType = new \ReflectionClass($type);
        }
        if ($refType !== null && $refType->isInterface()) {
            if ($this->hasMapper($type)) {
                throw new BindingException("Type " . $type . " is already known to the MapperRegistry.");
            }
            $loadCompleted = false;
            try {
                $this->knownMappers[$type] = new MapperProxyFactory($type);
                $parser = new MapperAnnotationBuilder($this->config, $type);
                $parser->parse();
                $loadCompleted = true;
            } finally {
                if (!$loadCompleted) {
                    unset($this->knownMappers[$type]);
                }
            }
        }
    }

    /**
     * Gets the mappers.
     *
     * @return the mappers
     */
    public function getMappers(): array
    {
        return array_keys($this->knownMappers);
    }

    /**
     * Adds the mappers.
     *
     * @param packageName
     *          the package name
     * @param superType
     *          the super type
     */
    public function addMappers(string $packageName, string $superType = 'object'): void
    {
        $resolverUtil = new ResolverUtil();
        $resolverUtil->find(new IsA($superType), $packageName);
        $mapperSet = $resolverUtil->getClasses();
        foreach ($mapperSet as $mapperClass) {
            $this->addMapper($mapperClass);
        }
    }
}
