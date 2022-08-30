<?php

namespace MyBatis\Reflection\Property;

class PropertyCopier
{
    private function __construct()
    {
    }

    public static function copyObjectProperties(?string $type, $sourceObject, $destinationObject): void
    {
        $parent = $type;
        while ($parent !== null && $parent !== false) {
            $vars = get_object_vars($sourceObject);
            foreach ($vars as $field => $value) {
                $destinationObject->{$field} = $value;
            }
            $parent = get_parent_class($parent);
        }
    }
}
