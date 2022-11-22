<?php

namespace MyBatis\Builder;

class ParameterExpression extends \ArrayObject
{
    public function __construct(string $expression)
    {
        $this->parse($expression);
    }

    private function parse(string $expression): void
    {
        $p = $this->skipWS($expression, 0);
        if ($expression[$p] == '(') {
            $this->expression($expression, $p + 1);
        } else {
            $this->property($expression, $p);
        }
    }

    private function expression(string $expression, int $left): void
    {
        $match = 1;
        $right = $left + 1;
        while ($match > 0) {
            if ($expression[$right] == ')') {
                $match--;
            } elseif ($expression[$right] == '(') {
                $match++;
            }
            $right++;
        }
        $this["expression"] = substr($expression, $left, $right - $left - 1);
        $this->sqlTypeOpt($expression, $right);
    }

    private function property(string $expression, int $left): void
    {
        if ($left < strlen($expression)) {
            $right = $this->skipUntil($expression, $left, ",:");
            $this["property"] = $this->trimmedStr($expression, $left, $right);
            $this->sqlTypeOpt($expression, $right);
        }
    }

    private function skipWS(string $expression, int $p): int
    {
        for ($i = $p; $i < strlen($expression); $i++) {
            if (!ctype_space($expression[$i])) {
                return $i;
            }
        }
        return strlen($expression);
    }

    private function skipUntil(string $expression, int $p, string $endChars): int
    {
        for ($i = $p; $i < strlen($expression); $i++) {
            $c = $expression[$i];
            if (strpos($endChars, $c) !== false) {
                return $i;
            }
        }
        return strlen($expression);
    }

    private function sqlTypeOpt(string $expression, int $p): void
    {
        $p = $this->skipWS($expression, $p);
        if ($p < strlen($expression)) {
            if ($expression[$p] == ':') {
                $this->sqlType($expression, $p + 1);
            } elseif ($expression[$p] == ',') {
                $this->option($expression, $p + 1);
            } else {
                throw new BuilderException("Parsing error in {" . $expression . "} in position " . $p);
            }
        }
    }

    private function sqlType(string $expression, int $p): void
    {
        $left = $this->skipWS($expression, $p);
        $right = $this->skipUntil($expression, $left, ",");
        if ($right > $left) {
            $this["dbalType"] = $this->trimmedStr($expression, $left, $right);
        } else {
            throw new BuilderException("Parsing error in {" . $expression . "} in position " . $p);
        }
        $this->option($expression, $right + 1);
    }

    private function option(string $expression, int $p): void
    {
        $left = $this->skipWS($expression, $p);
        if ($left < strlen($expression)) {
            $right = $this->skipUntil($expression, $left, "=");
            $name = $this->trimmedStr($expression, $left, $right);
            $left = $right + 1;
            $right = $this->skipUntil($expression, $left, ",");
            $value = $this->trimmedStr($expression, $left, $right);
            $this[$name] = $value;
            $this->option($expression, $right + 1);
        }
    }

    public function trimmedStr(string $str, int $start, int $end): string
    {
        while (ctype_space($str[$start])) {
            $start++;
        }
        while (ctype_space($str[$end - 1])) {
            $end--;
        }
        return $start >= $end ? "" : substr($str, $start, $end - $start);
    }

    public function get($index)
    {
        if (isset($this[$index])) {
            return $this[$index];
        } else {
            throw new \Exception(sprintf("Undefined array key %d", $index));
        }
    }
}
