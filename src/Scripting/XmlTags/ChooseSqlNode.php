<?php

namespace MyBatis\Scripting\XmlTags;

class ChooseSqlNode implements SqlNodeInterface
{
    private $defaultSqlNode;
    private $ifSqlNodes = [];

    public function __construct(array $ifSqlNodes, SqlNodeInterface $defaultSqlNode)
    {
        $this->ifSqlNodes = $ifSqlNodes;
        $this->defaultSqlNode = $defaultSqlNode;
    }

    public function apply(DynamicContext $context): bool
    {
        foreach ($this->ifSqlNodes as $sqlNode) {
            if ($sqlNode->apply($context)) {
                return true;
            }
        }
        if ($this->defaultSqlNode !== null) {
            $this->defaultSqlNode->apply($context);
            return true;
        }
        return false;
    }
}
