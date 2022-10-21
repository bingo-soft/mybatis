<?php

namespace MyBatis\Scripting\XmlTags;

interface SqlNodeInterface
{
    public function apply(DynamicContext $context): bool;
}
