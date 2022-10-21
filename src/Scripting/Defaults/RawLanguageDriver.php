<?php

namespace MyBatis\Scripting\Defaults;

use MyBatis\Builder\BuilderException;
use MyBatis\Mapping\SqlSourceInterface;
use MyBatis\Parsing\XNode;
use MyBatis\Scripting\XmlTags\XMLLanguageDriver;
use MyBatis\Session\Configuration;

class RawLanguageDriver extends XMLLanguageDriver
{
    public function createSqlSource(Configuration $configuration, /*XNode|string*/$script, string $parameterType): SqlSourceInterface
    {
        $source = parent::createSqlSource($configuration, $script, $parameterType);
        $this->checkIsNotDynamic($source);
        return $source;
    }

    private function checkIsNotDynamic(SqlSourceInterface $source): void
    {
        if (RawSqlSource::class !== get_class($source)) {
            throw new BuilderException("Dynamic content is not allowed when using RAW language");
        }
    }
}
