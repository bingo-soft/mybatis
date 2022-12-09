<?php

namespace MyBatis\Session;

use Doctrine\DBAL\Connection;

interface SqlSessionFactoryInterface
{
    public function openSession(/*?Connection*/$connection = null): SqlSessionInterface;
    public function getConfiguration(): Configuration;
}
