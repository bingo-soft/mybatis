<?php

namespace MyBatis\Scripting\XmlTags;

use MyBatis\Parsing\XNode;

interface NodeHandlerInterface
{
    public function handleNode(XNode $nodeToHandle, array &$targetContents): void;
}
