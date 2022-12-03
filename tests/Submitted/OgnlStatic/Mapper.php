<?php

namespace Tests\Submitted\OgnlStatic;

interface Mapper
{
    public function getUserStatic(?int $id): ?User;
    public function getUserIfNode(?string $id): ?User;
}
