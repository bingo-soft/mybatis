<?php

namespace MyBatis\Cursor\Defaults;

class CursorStatus
{
    /**
     * A freshly created cursor, database ResultSet consuming has not started.
     */
    public const CREATED = 'created';
    /**
     * A cursor currently in use, database ResultSet consuming has started.
     */
    public const OPEN = 'open';
    /**
     * A closed cursor, not fully consumed.
     */
    public const CLOSED = 'closed';
    /**
     * A fully consumed cursor, a consumed cursor is always closed.
     */
    public const CONSUMED = 'consumed';
}
