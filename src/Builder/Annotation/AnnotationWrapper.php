<?php

namespace MyBatis\Builder\Annotation;

use MyBatis\Annotations\{
    Delete,
    DeleteProvider,
    Insert,
    InsertProvider,
    Options,
    Select,
    SelectKey,
    SelectProvider,
    Update,
    UpdateProvider
};
use MyBatis\Mapping\SqlCommandType;

class AnnotationWrapper
{
    private $annotation;
    private $databaseId;
    private $sqlCommandType;

    public function __construct($annotation)
    {
        $this->annotation = $annotation;
        if ($annotation instanceof Select) {
            $this->databaseId = $annotation->databaseId();
            $sqlCommandType = SqlCommandType::SELECT;
        } elseif ($annotation instanceof Update) {
            $this->databaseId = $annotation->databaseId();
            $sqlCommandType = SqlCommandType::UPDATE;
        } elseif ($annotation instanceof Insert) {
            $this->databaseId = $annotation->databaseId();
            $sqlCommandType = SqlCommandType::INSERT;
        } elseif ($annotation instanceof Delete) {
            $this->databaseId = $annotation->databaseId();
            $sqlCommandType = SqlCommandType::DELETE;
        } elseif ($annotation instanceof SelectProvider) {
            $this->databaseId = $annotation->databaseId();
            $sqlCommandType = SqlCommandType::SELECT;
        } elseif ($annotation instanceof UpdateProvider) {
            $this->databaseId = $annotation->databaseId();
            $sqlCommandType = SqlCommandType::UPDATE;
        } elseif ($annotation instanceof InsertProvider) {
            $this->databaseId = $annotation->databaseId();
            $sqlCommandType = SqlCommandType::INSERT;
        } elseif ($annotation instanceof DeleteProvider) {
            $this->databaseId = $annotation->databaseId();
            $sqlCommandType = SqlCommandType::DELETE;
        } else {
            $sqlCommandType = SqlCommandType::UNKNOWN;
            if ($annotation instanceof Options) {
                $this->databaseId = $annotation->databaseId();
            } elseif ($annotation instanceof SelectKey) {
                $this->databaseId = $annotation->databaseId();
            } else {
                $this->databaseId = "";
            }
        }
    }

    public function getAnnotation()
    {
        return $this->annotation;
    }

    public function getSqlCommandType(): string
    {
        return $this->sqlCommandType;
    }

    public function getDatabaseId(): string
    {
        return $this->databaseId;
    }
}
