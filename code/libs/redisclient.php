<?php

/**
 * Redis Client Singleton
 */
class rc {

    const JOIN = ':';

    /* @var Predis\Client */
    static $redis;

    static function get() {
        if (!isset(self::$redis)) {
            $constring = defined('REDIS_CONNECTION_STRING') ? REDIS_CONNECTION_STRING : 'tcp://localhost:6379';
            self::$redis = new Predis\Client($constring);
            if (defined('REDIS_DBID')) {
                self::$redis->select(REDIS_DBID);
            }
        }
        return self::$redis;
    }

    /**
     * Create a key from a series of array pieces (you can also pass non-arrya items, but that's rather wasteful)
     * @static
     * @return string
     */
    static function key() {
        return implode(self::JOIN, func_get_args());
    }

}
