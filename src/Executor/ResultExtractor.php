<?php

namespace MyBatis\Executor;

use MyBatis\Session\Configuration;

class ResultExtractor
{
    private $configuration;

    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    public function extractObjectFromList(?array $list, ?string $targetType)
    {
        $value = null;
        if ($targetType !== null && $targetType == 'array') {
            $value = $list;
        } else {
            if (!empty($list) && count($list) > 1) {
                throw new ExecutorException("Statement returned more than one row, where no more than one was expected.");
            } elseif (!empty($list) && count($list) == 1) {
                $value = $list[0];
            }
        }
        return $value;
    }
}
