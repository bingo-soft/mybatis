<?php

namespace MyBatis\Session;

use MyBatis\Builder\Xml\XMLConfigBuilder;
use MyBatis\Exception\ExceptionFactory;
use MyBatis\Session\Defaults\DefaultSqlSessionFactory;

class SqlSessionFactoryBuilder
{
    public function build(/*Configuration|...*/$readerOrConfig, /*string|array*/$envOrProps = null, array $props = [])
    {
        if ($readerOrConfig instanceof Configuration) {
            return new DefaultSqlSessionFactory($readerOrConfig);
        }
        $environment = null;
        $properties = [];
        if (is_string($envOrProps)) {
            $environment = $envOrProps;
        } elseif (is_array($envOrProps)) {
            $properties = $envOrProps;
        }
        if (!empty($props)) {
            $properties = $props;
        }

        try {
            $parser = new XMLConfigBuilder($readerOrConfig, $environment, $properties);
            return $this->build($parser->parse());
        } catch (\Exception $e) {
            throw ExceptionFactory::wrapException("Error building SqlSession.", $e);
        } finally {
            try {
                if ($readerOrConfig !== null && is_resource($readerOrConfig)) {
                    $readerOrConfig->close();
                }
            } catch (\Exception $e) {
                // Intentionally ignore. Prefer previous error.
            }
        }
    }
}
