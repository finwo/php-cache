<?php

namespace Finwo\Cache;

interface CacheInterface
{
    /**
     * @param string $key
     * @param int $ttl
     * @return mixed
     */
    public function fetch($key = '', $ttl = 30);

    /**
     * @param string $key
     * @param $value
     * @param int $ttl
     * @return CacheInterface
     */
    public function store($key = '', $value, $ttl = 30);
}