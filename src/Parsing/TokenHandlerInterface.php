<?php

namespace MyBatis\Parsing;

interface TokenHandlerInterface
{
    public function handleToken(string $content): ?string;
}
