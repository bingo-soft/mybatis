<?php

namespace MyBatis\Plugin;

interface SignatureInterface
{
    public function type(): string;

    public function method(): string;

    public function args(): array;
}
