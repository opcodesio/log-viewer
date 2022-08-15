<?php

namespace Opcodes\LogViewer\Concerns;

use Illuminate\Support\Facades\Cache;

trait HasLocalCache
{
    /**
     * The container holding the cached values for this model.
     *
     * @var array
     */
    public array $_localCache = [];

    /**
     * Cache the result of a callback to the given key.
     */
    protected function cache(string $key, callable $callable, $ttl = null): mixed
    {
        if (! $this->hasLocalCache($key) && ! $this->hasRemoteCache($key)) {
            $this->cacheSet($callable(), $ttl);
        }

        return $this->cacheGet($key);
    }

    protected function hasLocalCache(string $key): bool
    {
        return array_key_exists($key, $this->_localCache);
    }

    protected function getLocalCache(string $key, $default = null): mixed
    {
        return $this->_localCache[$key] ?? $default;
    }

    protected function setLocalCache(string $key, mixed $value): void
    {
        $this->_localCache[$key] = $value;
    }

    protected function hasRemoteCache(string $key): bool
    {
        return Cache::has($key);
    }

    protected function getRemoteCache(string $key, $default = null): mixed
    {
        return Cache::get($key, $default);
    }

    protected function setRemoteCache(string $key, mixed $value, $ttl = null): void
    {
        Cache::put($key, $value, $ttl);
    }

    protected function clearRemoteCache(string $key): void
    {
        Cache::forget($key);
    }

    protected function cacheSet(string $key, mixed $value = null, $ttl = null): void
    {
        $this->setLocalCache($key, $value);
        $this->setRemoteCache($key, $value, $ttl);
    }

    /**
     * Get a value from the cache key
     */
    protected function cacheGet(string $key, $default = null): mixed
    {
        if ($this->hasLocalCache($key)) {
            return $this->getLocalCache($key, $default);
        }

        if ($this->hasRemoteCache($key)) {
            $this->setLocalCache($key, $this->getRemoteCache($key));

            return $this->getLocalCache($key);
        }

        return $default;
    }

    /**
     * Clear the cached result for a given key.
     */
    protected function clearLocalCache(string $key = null): void
    {
        if (is_null($key)) {
            $this->_localCache = [];
        } else {
            unset($this->_localCache[$key]);
        }
    }
}
