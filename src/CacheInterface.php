<?php

namespace Finwo\Cache;

interface CacheInterface
{
    /**
     * @param string $key
     * @param int    $ttl
     *
     * @return mixed
     */
    public function fetch($key = '', $ttl = 30);

    /**
     * @param string $key
     * @param        $value
     * @param int    $ttl
     *
     * @return mixed
     */
    public function store($key = '', $value, $ttl = 30);

    /**
     * @param      $callable
     * @param null $arguments
     * @param int  $ttl
     *
     * @return mixed
     */
    public function func($callable, $arguments = null, $ttl = 30);

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function request($key);
}
