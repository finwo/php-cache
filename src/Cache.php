<?php

namespace Finwo\Cache;

use Finwo\Datatools\ArrayQuery;

class Cache implements CacheInterface
{
    /**
     * Simple local cache implementation
     *
     * @var array
     */
    protected $localCache = array();

    /**
     * List of known & supported extensions
     *
     * @var array
     */
    private static $typeDetect = array(
        'memcached' => 'Finwo\\Cache\\Memcached'
    );

    public static function init( $type = 'detect' )
    {
        $class = self::class;

        if ($type == 'detect') {

            // Check if any of the known implementations exist
            foreach (self::$typeDetect as $test) {
                if (class_exists($test)) {
                    $class = $test;
                    break;
                }
            }

        } elseif (isset(self::$typeDetect[$type])) {

            // Set the implementation directly
            $class = self::$typeDetect[$type];
        }

        return new $class();
    }

    /**
     * Simple local cache implementation
     *
     * {@inheritdoc}
     */
    public function fetch($key = '', $ttl = 30)
    {
        // if we don't have it's cache, don't try to
        if (!isset($this->localCache[$key])) {
            return null;
        }

        // easy-access to the data
        $data = &$this->localCache[$key];

        // if we're not expired yet, it's east
        if ($data['expire'] > ($t = time())) {
            return $data['value'];
        }

        // the calling application may be refreshing already
        if ($data['lock'] > $t) {
            return $data['value'];
        }

        // give our callee some time to refresh
        $data['lock'] = $t + ($ttl / 4);

        // indicate the callee needs to refresh the cache
        return null;
    }

    /**
     * Simple local cache implementation
     *
     * {@inheritdoc}
     */
    public function store($key = '', $value, $ttl = 30)
    {
        $this->localCache[$key] = array(
            'expire' => time() + $ttl,
            'value'  => $value,
            'lock'   => 0
        );

        return $this;
    }
}