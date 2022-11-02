<?php

namespace MyBatis\Io;

class Resources
{
    public static function getResourceURL(string $resource): ?string
    {
        return $resource;
    }

    public static function getResourceAsStream(string $resource)
    {
        $resourceStream = null;
        if (file_exists($resource)) {
            $resourceStream = fopen($resource, 'r+');
        }
        return $resourceStream;
    }

    public static function getResourceAsFile(string $resource)
    {
        return self::getResourceAsStream($resource);
    }

    public static function getUrlAsStream(string $urlString)
    {
        return self::getResourceAsStream($urlString);
    }

    public static function getResourceAsProperties(string $resource): array
    {
        $props = [];
        $fp = self::getResourceAsStream($resource);
        while (($line = fgets($fp, 4096)) !== false) {
            $tokens = explode("=", $line);
            if (count($tokens) == 2) {
                $props[$tokens[0]] = trim($tokens[1]);
            }
        }
        fclose($fp);
        return $props;
    }

    public static function getUrlAsProperties(string $urlString): array
    {
        return self::getResourceAsProperties($urlString);
    }
}
