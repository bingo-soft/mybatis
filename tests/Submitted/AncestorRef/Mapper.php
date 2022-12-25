<?php

namespace Tests\Submitted\AncestorRef;

interface Mapper
{
    public function getUserAssociation(int $id): ?User;

    public function getUserCollection(int $id): ?User;

    public function selectBlog(int $id): ?Blog;
}
