<?php

namespace bsf\Database;

/**
 * Task服务类，提供Database task call.
 *
 * @author     gukai@bilibili.com
 */
class Dao
{
    protected static $_pool_map;
    protected static $_config_map;

    public function __construct($server, $config = [])
    {
        // task_worker_num task进程数
        // db_map 存储传入数据库配置
        $task_worker_num = 0;

        foreach ($config as $name => $item) {
            $maxconn = $item['maxconn'];
            static::$_pool_map[$name] = range($task_worker_num, $task_worker_num + $maxconn - 1);
            $task_worker_num += $maxconn;
            static::$_config_map[$name] = [
                'dsn' => $item['dsn'],
                'username' => $item['username'],
                'password' => $item['password'],
                'timeout' => $item['timeout'],
            ];
        }

        foreach (static::$_pool_map as $name => $task_ids) {
            static::$_pool_map[$name] = new DbPool($task_ids);
        }

        $server->set(['task_worker_num' => $task_worker_num]);
    }

    public function onTask($serv, $task_id, $from_id, $data)
    {
        \bsf\Log\Log::debug('Task Received! '.$data['model'].'::'.$data['method']);

        $callable = [
            new $data['model'](),
            $data['method'],
        ];
        // 如果没有连接数据库的话，初始化连接
        $config = static::getConfig($data['dbname']);
        try {
            PDOClient::connect($config);
        } catch (\Exception $e) {
            \bsf\Log\Log::error('PDO Exception: '.$e->getMessage());
            $serv->finish(['code' => 500, 'msg' => 'internal unavailable']);

            return;
        }
        $response = call_user_func_array($callable, $data['params']);

        // 链接过期在内部维护，不重试。
//        $max_retry = 1;
//        while (PDOClient::isAway() && $max_retry > 0) {
//            PDOClient::connect($config, true);
//            $response = call_user_func_array($callable, $data['params']);
//            $max_retry--;
//        }

        $serv->finish($response);

        return;
    }

    public static function getFreeTask($name)
    {
        return static::$_pool_map[$name]->getFreeWorker();
    }

    public static function freeTask($name, $task_id)
    {
        return static::$_pool_map[$name]->freeWorker($task_id);
    }

    public static function getConfig($name)
    {
        return static::$_config_map[$name];
    }
}
