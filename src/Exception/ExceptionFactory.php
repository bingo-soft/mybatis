<?php

namespace MyBatis\Exception;

class ExceptionFactory
{
    private function __construct()
    {
        // Prevent Instantiation
    }

    public static function wrapException(string $message, \Exception $e)
    {
        return new PersistenceException($message);
    }
}
