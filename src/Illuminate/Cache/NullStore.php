<?php

namespace Illuminate\Cache;

use Illuminate\Contracts\Cache\Store;

class NullStore extends TaggableStore implements Store
{
    /**
     * The array of stored values.
     *
     * @var array
     */
    protected $storage = [];

    /**
     * Retrieve an item from the cache by key.
     *
     * @param  string  $key
     * @return mixed
     */
    public function get($key)
    {
        //
    }

    /**
     * Retrieve multiple items from the cache by key,
     * items not found in the cache will have a null value for the key
     *
     * @param string[] $keys
     * @return array
     */
    public function getMulti(array $keys)
    {
        //
    }

    /**
     * Store an item in the cache for a given number of minutes.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @param  int     $minutes
     * @return void
     */
    public function put($key, $value, $minutes)
    {
        //
    }

    /**
     * Store multiple items in the cache for a set number of minutes
     *
     * @param array $values array of key => value pairs
     * @param int   $minutes
     * @return void
     */
    public function putMulti(array $values, $minutes)
    {
        //
    }

    /**
     * Increment the value of an item in the cache.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return int
     */
    public function increment($key, $value = 1)
    {
        //
    }

    /**
     * Increment the value of an item in the cache.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return int
     */
    public function decrement($key, $value = 1)
    {
        //
    }

    /**
     * Store an item in the cache indefinitely.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public function forever($key, $value)
    {
        //
    }

    /**
     * Remove an item from the cache.
     *
     * @param  string  $key
     * @return void
     */
    public function forget($key)
    {
        //
    }

    /**
     * Remove all items from the cache.
     *
     * @return void
     */
    public function flush()
    {
        //
    }

    /**
     * Get the cache key prefix.
     *
     * @return string
     */
    public function getPrefix()
    {
        return '';
    }
}
