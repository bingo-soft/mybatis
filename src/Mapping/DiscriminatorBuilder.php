<?php

namespace MyBatis\Mapping;

use MyBatis\Session\Configuration;

class DiscriminatorBuilder
{
    private $discriminator;

    public function __construct(Configuration $configuration, ResultMapping $resultMapping, array $discriminatorMap)
    {
        $this->discriminator = new Discriminator();
        $this->discriminator->resultMapping = $resultMapping;
        $this->discriminator->discriminatorMap = $discriminatorMap;
    }

    public function build(): Discriminator
    {
        if ($this->discriminator->resultMapping === null || empty($this->discriminator->discriminatorMap)) {
            throw new \Exception("Invalid discriminator");
        }
        return $this->discriminator;
    }
}
