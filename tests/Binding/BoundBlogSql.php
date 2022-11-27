<?php

namespace Tests\Binding;

class BoundBlogSql
{
    public function selectBlogsSql(): string
    {
        return "SELECT * FROM BLOG";
    }
}
