<?php

namespace MyBatis\Scripting\XmlTags;

use MyBatis\Parsing\TokenHandlerInterface;

class DynamicCheckerTokenParser implements TokenHandlerInterface
{
    private $isDynamic;

    public function __construct()
    {
        // Prevent Synthetic Access
    }

    public function isDynamic(): bool
    {
        return $this->isDynamic;
    }

    public function handleToken(string $content): ?string
    {
        $this->isDynamic = true;
        return null;
    }
}
