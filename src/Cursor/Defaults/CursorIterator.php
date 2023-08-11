<?php

namespace MyBatis\Cursor\Defaults;

use MyBatis\Cursor\CursorInterface;
use MyBatis\Session\ResultHandlerInterface;

class CursorIterator
{
    private $scope;

    private $objectWrapperResultHandler;

    /**
     * Holder for the next object to be returned.
     */
    public $object;

    /**
     * Index of objects returned using next(), and as such, visible to users.
     */
    public $iteratorIndex = -1;

    public function __construct(CursorInterface $scope, ResultHandlerInterface $objectWrapperResultHandler)
    {
        $this->scope = $scope;
        $this->objectWrapperResultHandler = $objectWrapperResultHandler;
    }

    public function valid(): bool
    {
        if (!$this->objectWrapperResultHandler->fetched) {
            $this->object = $this->scope->fetchNextUsingRowBound();
        }
        return $this->objectWrapperResultHandler->fetched;
    }

    public function hasNext(): bool
    {
        return $this->valid();
    }

    public function current()
    {
        return $this->object;
    }

    public function next()
    {
        // Fill next with object fetched from hasNext()
        $next = $this->object;

        if (!$this->objectWrapperResultHandler->fetched) {
            $next = $this->scope->fetchNextUsingRowBound();
        }

        if ($this->objectWrapperResultHandler->fetched) {
            $this->objectWrapperResultHandler->fetched = false;
            $this->object = null;
            $this->iteratorIndex += 1;
            return $next;
        }
        throw new \Exception("No such element");
    }
}
