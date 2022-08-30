<?php

namespace MyBatis\Cursor;

interface CursorInterface
{
    /**
     * @return bool true if the cursor has started to fetch items from database.
     */
    public function isOpen(): bool;

    /**
     *
     * @return bool true if the cursor is fully consumed and has returned all elements matching the query.
     */
    public function isConsumed(): bool;

    /**
     * Get the current item index. The first item has the index 0.
     *
     * @return int -1 if the first cursor item has not been retrieved. The index of the current item retrieved.
     */
    public function getCurrentIndex(): int;
}
