<?php

namespace MyBatis\Plugin;

#[Attribute]
class Signature implements SignatureInterface
{
    private $type;
    private $method;
    private $args;

    public function __construct(string $type, string $method, array $args = [])
    {
        $this->type = $type;
        $this->method = $method;
        $this->args = $args;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function method(): string
    {
        return $this->method;
    }

    public function args(): array
    {
        return $this->args;
    }
}
