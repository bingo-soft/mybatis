<?php

namespace MyBatis\Session;

class Ambiguity
{
    public function __construct(private string $subject = "")
    {
    }

    public function getSubject(): string
    {
        return $this->subject;
    }
}
