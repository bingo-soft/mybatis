<?php

namespace Tests\Parsing;

use MyBatis\Parsing\TokenHandlerInterface;

class VariableTokenHandler implements TokenHandlerInterface
{
    private $variables = [];

    public function __construct(array $variables)
    {
        $this->variables = $variables;
    }

    public function handleToken(string $content): string
    {
        if (array_key_exists($content, $this->variables)) {
            return $this->variables[$content];
        }
        return "null";
    }
}
