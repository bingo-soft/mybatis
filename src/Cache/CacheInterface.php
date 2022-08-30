<?php

namespace MyBatis\Cache;

interface CacheInterface
{
    /**
     * @return The identifier of this cache
     */
    public function getId(): string;

    /**
     * @param key
     *          Can be any object but usually it is a {@link CacheKey}
     * @param value
     *          The result of a select.
     */
    public function putObject($key, $value): void;

    /**
     * @param key
     *          The key
     * @return The object stored in the cache.
     */
    public function getObject($key);

    /**
     * As of 3.3.0 this method is only called during a rollback
     * for any previous value that was missing in the cache.
     * This lets any blocking cache to release the lock that
     * may have previously put on the key.
     * A blocking cache puts a lock when a value is null
     * and releases it when the value is back again.
     * This way other threads will wait for the value to be
     * available instead of hitting the database.
     *
     *
     * @param key
     *          The key
     * @return Not used
     */
    public function removeObject($key);

    /**
     * Clears this cache instance.
     */
    public function clear(): void;

    /**
     * Optional. This method is not called by the core.
     *
     * @return The number of elements stored in the cache (not its capacity).
     */
    public function getSize(): int;
}
