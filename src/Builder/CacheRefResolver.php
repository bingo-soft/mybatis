<?php

namespace MyBatis\Builder;

use MyBatis\Cache\CacheInterface;

class CacheRefResolver
{
    private $assistant;
    private $cacheRefNamespace;

    public function __construct(MapperBuilderAssistant $assistant, string $cacheRefNamespace)
    {
        $this->assistant = $assistant;
        $this->cacheRefNamespace = $cacheRefNamespace;
    }

    public function resolveCacheRef(): CacheInterface
    {
        return $this->assistant->useCacheRef($this->cacheRefNamespace);
    }
}
