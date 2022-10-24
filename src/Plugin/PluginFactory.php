<?php

namespace MyBatis\Plugin;

use Util\Proxy\ProxyFactory;
use Util\Reflection\{
    MapUtil,
    MetaClass,
    MetaObject
};

class PluginFactory
{
    public static function wrap($target, Interceptor $interceptor)
    {
        $signatureMap = self::getSignatureMap($interceptor);
        $interfaces = self::getAllInterfaces($target, $signatureMap);
        $enhancer = new ProxyFactory();
        $enhancer->setSuperclass(get_class($target));
        if (!empty($interfaces)) {
            $enhancer->setInterfaces($interfaces);
        }
        $proxy = $enhancer->create([]);
        $proxy->setHandler(new Plugin($target, $interceptor, $signatureMap));
        return $proxy;
    }

    private static function getSignatureMap(Interceptor $interceptor): array
    {
        $refInterceptor = new MetaObject($interceptor);
        $refAttributes = $refInterceptor->getAttributes(Intercepts::class);
        if (empty($refAttributes)) {
            throw new PluginException("No Intercepts annotation was found in interceptor " . get_class($interceptor));
        }
        $interceptsAnnotation = $refAttributes[0];
        $sigs = $interceptsAnnotation->getArguments();
        $signatureMap = [];
        foreach ($sigs[0] as $sig) {
            $methods = &MapUtil::computeIfAbsent($signatureMap, $sig->type(), function () {
                return [];
            });
            try {
                $method = (new MetaClass($sig->type()))->getMethod($sig->method());
                $methods[] = $method;
            } catch (\Exception $e) {
                throw new \Exception("Could not find method on " . $sig->type() . " named " . $sig->method() . ". Cause: " . $e->getMessage());
            }
        }
        return $signatureMap;
    }

    private static function getAllInterfaces($type, array $signatureMap): array
    {
        $interfaces = [];
        $ref = new MetaObject($type);
        while ($ref !== null && $ref !== false) {
            foreach ($ref->getInterfaceNames() as $c) {
                if (array_key_exists($c, $signatureMap)) {
                    $interfaces[] = $c;
                }
            }
            $ref = $ref->getParentClass();
        }
        return $interfaces;
    }
}
