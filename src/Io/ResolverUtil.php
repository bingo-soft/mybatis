<?php

namespace MyBatis\Io;

class ResolverUtil
{
    private $matches = [];

    public function getClasses(): array
    {
        return $this->matches;
    }

    public function findImplementations(string $parent, array $packageNames): ResolverUtil
    {
        if (empty($packageNames)) {
            return $this;
        }

        $test = new IsA($parent);
        foreach ($packageNames as $pkg) {
            $this->find($test, $pkg);
        }

        return $this;
    }

    public function find(TestInterface $test, string $packageName): ResolverUtil
    {
        $path = $this->getPackagePath($packageName);

        $it = new \RecursiveDirectoryIterator($path);
        foreach (new \RecursiveIteratorIterator($it) as $file) {
            if ($file->getExtension() == 'php') {
                $this->addIfMatching($test, $file->getPathname());
            }
        }

        return $this;
    }

    protected function getPackagePath(string $packageName): string
    {
        return str_replace(['.', '\\'], '/', $packageName);
    }

    protected function addIfMatching(TestInterface $test, string $path): void
    {
        $externalName = str_replace('/', '\\', substr($path, 0, strpos($path, '.')));
        $namespace = $this->getNamespaceFromPath($path);
        if ($namespace !== null) {
            $parts = explode('/', $path);
            $last = $parts[count($parts) - 1];
            $type = $namespace . '\\' . substr($last, 0, strpos($last, '.'));
            if ($test->matches($type)) {
                $this->matches[] = $type;
            }
        }
    }

    private function getNamespaceFromPath(string $path): ?string
    {
        $src = file_get_contents($path);
        $tokens = token_get_all($src);
        $count = count($tokens);
        $i = 0;
        $namespace = '';
        $namespaceOk = false;
        while ($i < $count) {
            $token = $tokens[$i];
            if (is_array($token) && $token[0] === T_NAMESPACE) {
                while (++$i < $count) {
                    if ($tokens[$i] === ';') {
                        $namespaceOk = true;
                        $namespace = trim($namespace);
                        break;
                    }
                    $namespace .= is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
                }
                break;
            }
            $i++;
        }

        if (!$namespaceOk) {
            return null;
        } else {
            return $namespace;
        }
    }
}
