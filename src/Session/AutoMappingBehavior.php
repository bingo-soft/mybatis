<?php

namespace MyBatis\Session;

class AutoMappingBehavior
{
    /**
     * Disables auto-mapping.
     */
    public const NONE = "none";

    /**
     * Will only auto-map results with no nested result mappings defined inside.
     */
    public const PARTIAL = "partial";

    /**
     * Will auto-map result mappings of any complexity (containing nested or otherwise).
     */
    public const FULL = "full";
}
