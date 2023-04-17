<?php

namespace MyBatis\Io;

class Resources
{
    public static function getResourceURL(string $resource): ?string
    {
        return $resource;
    }

    public static function getResourceAsStream(string $resource, ?array $dirs = [])
    {
        $resourceStream = null;
        if (file_exists($resource)) {
            $resourceStream = fopen($resource, 'r+');
        } else {
            //lookup in relative Resources folder
            $parts = explode(DIRECTORY_SEPARATOR, $resource);
            $root = array_shift($parts);
            $relativePath = implode(DIRECTORY_SEPARATOR, $parts);
            if (!file_exists($root) && file_exists($root = strtolower($root))) {
            }
            if (!empty($dirs)) {
                foreach ($dirs as $root) {
                    $newResource = implode(DIRECTORY_SEPARATOR, [$root, $resource]);
                    if (file_exists($newResource)) {
                        return fopen($newResource, 'r+');
                    }
                }
            }
            $resource = implode(DIRECTORY_SEPARATOR, [$root, 'Resources', $relativePath]);
            if (file_exists($resource)) {
                $resourceStream = fopen($resource, 'r+');
            }
        }
        return $resourceStream;
    }

    public static function getResourceAsFile(string $resource)
    {
        return self::getResourceAsStream($resource);
    }

    public static function getUrlAsStream(string $urlString, ?array $dirs = [])
    {
        return self::getResourceAsStream($urlString, $dirs);
    }

    public static function getResourceAsProperties(string $resource, ?array $dirs = []): array
    {
        $props = [];
        $fp = self::getResourceAsStream($resource, $dirs);
        if ($fp !== null) {
            while (($line = fgets($fp, 4096)) !== false) {
                $tokens = explode("=", $line);
                if (count($tokens) == 2) {
                    $props[$tokens[0]] = trim($tokens[1]);
                }
            }
            fclose($fp);
        }
        return $props;
    }

    public static function getUrlAsProperties(string $urlString, ?array $dirs = []): array
    {
        return self::getResourceAsProperties($urlString, $dirs);
    }
}
