<?php

namespace MyBatis\Cursor\Defaults;

use MyBatis\Cursor\CursorInterface;
use MyBatis\Executor\ResultSet\{
    DefaultResultSetHandler,
    ResultSetWrapper
};
use MyBatis\Mapping\ResultMap;
use MyBatis\Session\{
    ResultContextInterface,
    ResultHandlerInterface,
    RowBounds
};

class DefaultCursor implements CursorInterface
{
    // ResultSetHandler stuff
    private $resultSetHandler;
    private $resultMap;
    private $rsw;
    private $rowBounds;
    protected $objectWrapperResultHandler;

    private $cursorIterator;
    private $iteratorRetrieved;

    private $status = CursorStatus::CREATED;
    private $indexWithRowBound = -1;

    public function __construct(DefaultResultSetHandler $resultSetHandler, ResultMap $resultMap, ResultSetWrapper $rsw, RowBounds $rowBounds)
    {
        $this->objectWrapperResultHandler = new ObjectWrapperResultHandler();
        $this->cursorIterator = new CursorIterator($this, $this->objectWrapperResultHandler);
        $this->resultSetHandler = $resultSetHandler;
        $this->resultMap = $resultMap;
        $this->rsw = $rsw;
        $this->rowBounds = $rowBounds;
    }

    public function isOpen(): bool
    {
        return $this->status == CursorStatus::OPEN;
    }

    public function isConsumed(): bool
    {
        return $this->status == CursorStatus::CONSUMED;
    }

    public function getCurrentIndex(): int
    {
        return $this->rowBounds->getOffset() + $this->cursorIterator->iteratorIndex;
    }

    public function iterator()
    {
        if ($this->iteratorRetrieved) {
            throw new \Exception("Cannot open more than one iterator on a Cursor");
        }
        if ($this->isClosed()) {
            throw new \Exception("A Cursor is already closed.");
        }
        $this->iteratorRetrieved = true;
        return $this->cursorIterator;
    }

    public function close(): void
    {
        if ($this->isClosed()) {
            return;
        }

        $rs = $rsw->getResultSet();
        try {
            if ($rs !== null) {
                $rs->free();
            }
        } catch (\Exception $e) {
            // ignore
        } finally {
            $this->status = CursorStatus::CLOSED;
        }
    }

    protected function fetchNextUsingRowBound()
    {
        $result = $this->fetchNextObjectFromDatabase();
        while ($this->objectWrapperResultHandler->fetched && $this->indexWithRowBound < $this->rowBounds->getOffset()) {
            $result = $this->fetchNextObjectFromDatabase();
        }
        return $result;
    }

    protected function fetchNextObjectFromDatabase()
    {
        if ($this->isClosed()) {
            return null;
        }

        $this->objectWrapperResultHandler->fetched = false;
        $this->status = CursorStatus::OPEN;
        //if (!$this->rsw->getResultSet()->isClosed()) {
        $this->resultSetHandler->handleRowValues($this->rsw, $this->resultMap, $this->objectWrapperResultHandler, RowBounds::default(), null);

        $next = $this->objectWrapperResultHandler->result;
        if ($this->objectWrapperResultHandler->fetched) {
            $this->indexWithRowBound += 1;
        }
        // No more object or limit reached
        if (!$this->objectWrapperResultHandler->fetched || $this->getReadItemsCount() == $this->rowBounds->getOffset() + $this->rowBounds->getLimit()) {
            $this->close();
            $this->status = CursorStatus::CONSUMED;
        }
        $this->objectWrapperResultHandler->result = null;

        return $next;
    }

    private function isClosed(): bool
    {
        return $this->status == CursorStatus::CLOSED || $this->status == CursorStatus::CONSUMED;
    }

    private function getReadItemsCount(): int
    {
        return $this->indexWithRowBound + 1;
    }
}
