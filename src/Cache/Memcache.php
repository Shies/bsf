<?php

namespace bsf\Cache;

class Memcache
{
    protected static $config;
    protected static $auth_instance;

    /**
     * 获取memcached实例.
     *
     * @return mixed
     */
    public static function getAuthInstance()
    {
        if (!static::$config['auth']['host'] || !static::$config['auth']['port']) {
            return false;
        }

        if (null === static::$auth_instance) {
            \bsf\Log\Log::debug('init memcache!');
            static::$auth_instance = new \Memcached();
            static::$auth_instance->setOption(\Memcached::OPT_CONNECT_TIMEOUT, 500);
            static::$auth_instance->addServer(static::$config['auth']['host'], static::$config['auth']['port']);
        }

        return static::$auth_instance;
    }

    /**
     * 配置memcached, 需要在获取实例前调用该方法.
     *
     * @param $config
     *
     * @return bool
     */
    public static function configure($config)
    {
        static::$config = $config;
    }

    /**
     * 获取认证的memcache.
     *
     * @return bool
     */
    public function getAuthMemcache()
    {
        if (!static::$config['auth']['host'] || !static::$config['auth']['port']) {
            return false;
        }

        \bsf\Log\Log::debug('get auth memcache!');
        $memcached = new \Memcached();
        $memcached->setOption(\Memcached::OPT_CONNECT_TIMEOUT, 100);
        $memcached->addServer(static::$config['auth']['host'], static::$config['auth']['port']);

        return $memcached;
    }
}
