<?php

namespace MyBatis\Scripting;

use Util\Reflection\MapUtil;

class LanguageDriverRegistry
{
    private $languageDriverMap = [];

    private $defaultDriverClass;

    public function register(?string $cls): void
    {
        if (empty($cls)) {
            throw new \Exception("null is not a valid Language Driver");
        }
        if (is_string($cls)) {
            MapUtil::computeIfAbsent($this->languageDriverMap, $cls, function () use ($cls) {
                try {
                    return new $cls();
                } catch (\Throwable $ex) {
                    throw new ScriptingException("Failed to load language driver for $cls");
                }
            });
        } elseif ($cls instanceof LanguageDriverInterface) {
            $instance = $cls;
            $cls = get_class($instance);
            if (!array_key_exists($cls, $this->languageDriverMap)) {
                $this->languageDriverMap[$cls] = $instance;
            }
        }
    }

    public function getDriver(?string $cls): ?LanguageDriverInterface
    {
        if (!empty($cls) && array_key_exists($cls, $this->languageDriverMap)) {
            return $this->languageDriverMap[$cls];
        }
        return null;
    }

    public function getDefaultDriver(): LanguageDriverInterface
    {
        return $this->getDriver($this->getDefaultDriverClass());
    }

    public function getDefaultDriverClass(): ?string
    {
        return $this->defaultDriverClass;
    }

    public function setDefaultDriverClass(string $defaultDriverClass): void
    {
        $this->register($defaultDriverClass);
        $this->defaultDriverClass = $defaultDriverClass;
    }
}
