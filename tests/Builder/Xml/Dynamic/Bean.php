<?php

namespace Tests\Builder\Xml\Dynamic;

class Bean
{
    public $id;

    public function __construct(?string $property)
    {
        $this->id = $property;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(string $property): void
    {
        $this->id = $property;
    }
}
