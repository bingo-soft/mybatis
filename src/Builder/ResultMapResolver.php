<?php

namespace MyBatis\Builder;

use MyBatis\Mapping\{
    Discriminator,
    ResultMap,
    ResultMapping
};

class ResultMapResolver
{
    private $assistant;
    private $id;
    private $type;
    private $extend;
    private $discriminator;
    private $resultMappings;
    private $autoMapping;

    public function __construct(MapperBuilderAssistant $assistant, string $id, string $type, string $extend, Discriminator $discriminator, array $resultMappings, bool $autoMapping)
    {
        $this->assistant = $assistant;
        $this->id = $id;
        $this->type = $type;
        $this->extend = $extend;
        $this->discriminator = $discriminator;
        $this->resultMappings = $resultMappings;
        $this->autoMapping = $autoMapping;
    }

    public function resolve(): ResultMap
    {
        return $this->assistant->addResultMap($this->id, $this->type, $this->extend, $this->discriminator, $this->resultMappings, $this->autoMapping);
    }
}
