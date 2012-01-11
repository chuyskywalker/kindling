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
            //self::$redis->connect(REDIS_HOST, 6379, 5);
            $h = defined('REDIS_HOST') ? REDIS_HOST : 'localhost';
            $p = defined('REDIS_PORT') ? REDIS_PORT : 6379;
            $t = defined('REDIS_CONNECT_TIMEOUT') ? REDIS_CONNECT_TIMEOUT : 5;
            self::$redis->connect($h, $p, $t);
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
