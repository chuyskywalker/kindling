<?php

/**
 * Redis Client Singleton
 */
class rc {

    const JOIN = ':';

    /** @var Redis */
    static $redis;

    static function get() {
        if (!isset(self::$redis)) {
            self::$redis = new Redis();
            self::$redis->connect('localhost', 6379, 5);
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
