<?php

namespace Finwo\Cache;

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

    /**
     * @param string $type
     * @param array  $options
     *
     * @return CacheInterface
     */
    public static function init( $type = 'detect', $options = array())
    {
        // We know ourselves, no need to be fancy about it
        $class = 'Finwo\\Cache\\Cache';

        if ($type == 'detect') {

            // Check if any of the known implementations exist
            foreach (self::$typeDetect as $test) {
                if (\class_exists($test)) {

                    $testObject = new $test($options);
                    if ($testObject instanceof CacheInterface) {
                        return $testObject;
                    }

                    break;
                }
            }

        } elseif (isset(self::$typeDetect[$type])) {

            // Set the implementation directly
            $class = self::$typeDetect[$type];
        }

        return new $class($options);
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
            return false;
        }

        // easy-access to the data
        $data = &$this->localCache[$key];

        // if we're not expired yet, it's east
        if ($data['expire'] > ($t = \time())) {
            return $data['value'];
        }

        // the calling application may be refreshing already
        if ($data['lock'] > $t) {
            return $data['value'];
        }

        // give our callee some time to refresh
        $data['lock'] = $t + ($ttl / 4);

        // indicate the callee needs to refresh the cache
        return false;
    }

    /**
     * Simple local cache implementation
     *
     * {@inheritdoc}
     */
    public function store($key = '', $value, $ttl = 30)
    {
        $this->localCache[$key] = array(
            'expire' => \time() + $ttl,
            'value'  => $value,
            'lock'   => 0
        );

        return $this;
    }

    /**
     * Simple implementation of function caching
     *
     * {@inheritdoc}
     */
    public function func($callable, $arguments = null, $ttl = 30)
    {
        // Validate callable
        if (!\is_callable($callable)) {
            return null;
        }

        // Build hash
        $hash = $this->hashVar($callable) . $this->hashVar($arguments);

        // The easy part
        if ( $result = $this->fetch($hash, $ttl) ) {
            return $result;
        }

        // Generate & store
        $this->store($hash, $result = \call_user_func_array($callable, $arguments), $ttl);

        return $result;
    }

    /**
     * Caches the current request
     * Meant for usage in pure php, not for frameworks like symfony
     * You should not call this before authentication
     *
     * {@inheritdoc}
     */
    public function request($hash, $ttl = 30)
    {
        // Use cache if possible
        if ($result = $this->fetch($hash, $ttl)) {
            $result = \unserialize($result);
            \http_response_code($result['code']);
            foreach ($result['headers'] as $value) {
                \header($value);
            }
            \header('X-Cache: HIT');
            die($result['body']);
        }

        // Notify we've missed the cache
        \header('X-Cache: MISS');

        // Create an alias, because you can't reference $this
        $cache = $this;

        // Register function on shutdown, to store data
        \ob_start(function($buffer) use ($hash, $cache, $ttl) {

            $code = \http_response_code();
            if ($code >= 200 && $code < 400) {
                $cache->store($hash, \serialize(array(
                    'code'    => \http_response_code(),
                    'headers' => \headers_list(),
                    'body'    => $buffer,
                )), $ttl);
            }

            // Do not change the data
            return $buffer;
        });
    }

    /**
     * Hash variable
     */
    protected function hashVar( $variable, $algo = 'md5' )
    {
        // Try the obvious
        if (\is_string($variable)) {
            return \hash($algo, $variable);
        }

        // Try serialized version
        try {
            return \hash($algo, \serialize($variable));
        } catch (\Exception $e) {
            // Do nothing, we haven't exactly crashed yet
        }

        // Try json encoded version
        try {
            return \hash($algo, \json_encode($variable));
        } catch (\Exception $e) {
            // Do nothing, we haven't exactly crashed yet
        }

        // Try var_export version
        try {
            return \hash($algo, \var_export($variable, true));
        } catch (\Exception $e) {
            // Do nothing, we haven't exactly crashed yet
        }

        // Try print_r version
        try {
            return \hash($algo, \print_r($variable, true));
        } catch (\Exception $e) {
            // Do nothing, we haven't exactly crashed yet
        }

        return null;
    }
}
