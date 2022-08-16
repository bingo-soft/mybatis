<?php

namespace MyBatis\Parsing;

class GenericTokenParser
{
    private $openToken;
    private $closeToken;
    private $handler;

    public function __construct(string $openToken, string $closeToken, TokenHandlerInterface $handler)
    {
        $this->openToken = $openToken;
        $this->closeToken = $closeToken;
        $this->handler = $handler;
    }

    public function parse(string $text): string
    {
        if (empty($text)) {
            return "";
        }
        // search open token
        $start = strpos($text, $this->openToken);
        if ($start === false) {
            return $text;
        }
        $src = $text;
        $offset = 0;
        $builder = "";
        $expression = null;
        do {
            if ($start > 0 && $src[$start - 1] == '\\') {
                // this open token is escaped. remove the backslash and continue.
                $builder .= substr($src, $offset, $start - $offset - 1) . $this->openToken;
                $offset = $start + strlen($this->openToken);
            } else {
                // found open token. let's search close token.
                if ($expression == null) {
                    $expression = "";
                } else {
                    $expression = null;
                }
                $builder .= substr($src, $offset, $start - $offset);
                $offset = $start + strlen($this->openToken);
                $end = strpos($text, $this->closeToken, $offset);
                while ($end !== false) {
                    if ($end > $offset && $src[$end - 1] == '\\') {
                        // this close token is escaped. remove the backslash and continue.
                        $expression .= substr($src, $offset, $end - $offset - 1) . $this->closeToken;
                        $offset = $end + strlen($this->closeToken);
                        $end = strpos($text, $this->closeToken, $offset);
                    } else {
                        $expression .= substr($src, $offset, $end - $offset);
                        break;
                    }
                }
                if ($end === false) {
                    // close token was not found.
                    $builder .= substr($src, $start, strlen($src) - $start);
                    $offset = strlen($src);
                } else {
                    $builder .= $this->handler->handleToken($expression);
                    $offset = $end + strlen($this->closeToken);
                }
            }
            $start = strpos($text, $this->openToken, $offset);
        } while ($start !== false);
        if ($offset < strlen($src)) {
            $builder .= substr($src, $offset, strlen($src) - $offset);
        }
        return $builder;
    }
}
