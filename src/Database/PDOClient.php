<?php

namespace bsf\Database;

/**
 * PDO连接类 维护prepare statement.
 *
 * @author     gukai@bilibili.com
 */
class PDOClient
{
    protected static $_link;
    protected static $_last_error;
    protected static $last_timestamp;

    public static function connect($config, $reconnect = false)
    {
        if (static::$_link === null || $reconnect || static::isExpired(@$config['timeout'])) {
            \bsf\Log\Log::debug(__CLASS__.' init pdo!');

            if (!isset($config['options'][\PDO::MYSQL_ATTR_INIT_COMMAND])) {
                $config['options'][\PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET NAMES utf8';
                $config['options'][\PDO::ATTR_PERSISTENT] = true;
            }
            static::$_link = [
                'conn' => new \PDO($config['dsn'], $config['username'], $config['password'], $config['options']),
                'stmt' => [],
            ];
        }

        static::refresh();

        return static::$_link;
    }

    public static function getConn()
    {
        return static::$_link['conn'];
    }

    public static function getStmt($sql)
    {
        $md5 = md5($sql);
        if (!isset(static::$_link['stmt'][$md5])) {
            static::$_link['stmt'][$md5] = static::$_link['conn']->prepare($sql);
        }

        return static::$_link['stmt'][$md5];
    }

    public static function isExpired($timeout)
    {
        if (static::$last_timestamp && isset($timeout) && $timeout != 0) {
            return (time() - static::$last_timestamp) > $timeout;
        }

        return false;
    }

    public static function refresh()
    {
        static::$last_timestamp = time();
    }

    public static function lastError($error = null)
    {
        if ($error === null) {
            return static::$_last_error;
        } else {
            static::$_last_error = $error;
        }
    }

    public static function isAway()
    {
        $error = static::lastError();

        return $error['code'] == 'HY000';
    }
}
