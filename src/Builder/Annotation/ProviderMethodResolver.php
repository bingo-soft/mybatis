<?php

namespace MyBatis\Builder\Annotation;

abstract class ProviderMethodResolver
{
    private $allMethods = [];
    private $ref;

    /**
     * Resolve an SQL provider method.
     *
     * <p> The default implementation return a method that matches following conditions.
     * <ul>
     *   <li>Method name matches with mapper method</li>
     *   <li>Return type matches the {@link CharSequence}({@link String}, {@link StringBuilder}, etc...)</li>
     * </ul>
     * If matched method is zero or multiple, it throws a {@link BuilderException}.
     *
     * @param context a context for SQL provider
     * @return an SQL provider method
     * @throws BuilderException Throws when cannot resolve a target method
     */
    public function resolveMethod(ProviderContext $context): \ReflectionMethod
    {
        if ($this->ref === null) {
            $this->ref = new \ReflectionClass($this);
            $this->allMethods = $this->ref->getMethods();
        }
        $sameNameMethods = array_filter(
            function ($method) use ($context) {
                return $method->name == $context->getMapperMethod()->name;
            },
            $this->allMethods
        );

        if (empty($sameNameMethods)) {
            throw new BuilderException(
                "Cannot resolve the provider method because '"
                . $context->getMapperMethod()->name . "' not found in SqlProvider '" . $this->ref->name . "'."
            );
        }

        $targetMethods = array_filter(function ($m) {
            if ($m->hasReturnType()) {
                $refType = $m->getReturnType();
                if ($refType instanceof \ReflectionNamedType) {
                    $type = $refType->getName();
                    return $type == 'string';
                }
            }
            return false;
        }, $sameNameMethods);

        if (count($targetMethods) == 1) {
            return $targetMethods[0];
        }
        if (empty($targetMethods)) {
            throw new BuilderException(
                "Cannot resolve the provider method because '"
                . $context->getMapperMethod()->name . "' does not return the CharSequence or its subclass in SqlProvider '"
                . $this->ref->name . "'."
            );
        } else {
            throw new BuilderException(
                "Cannot resolve the provider method because '"
                . $context->getMapperMethod()->name . "' is found multiple in SqlProvider '" . $this->ref->name . "'."
            );
        }
    }
}
