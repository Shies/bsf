<?php

namespace bsf;

use bsf\Http\Foundation;
use bsf\Tcp\Package;

/**
 * 服务基类，封装server服务
 *
 * @author  gukai@bilibili.com
 *
 * @example (new Server())->setName('demo')->setPath('/var/run')->prepare()->start()
 */
class Server
{
    protected static $_router;
    protected $server;          // server instance
    protected $host;            // host
    protected $port;            // port
    protected $config;          // server config
    protected $name = 'demo';   // process name
    protected $path = '/tmp';   // stored pid files

    public function __construct($host = '0.0.0.0', $port = 9501, $config = [])
    {
        $this->host = $host;
        $this->port = $port;
        $this->config = $config;
    }

    public static function getRouter()
    {
        if (null === static::$_router) {
            static::$_router = new Router();
        }

        return static::$_router;
    }

    public static function setRouter($router)
    {
        static::$_router = $router;
    }

    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function setPath($path)
    {
        if (is_dir($path) && is_writable($path)) {
            $this->path = rtrim($path, '/');
        }

        return $this;
    }

    public function getPath()
    {
        return "{$this->path}/{$this->name}.pid";
    }

    public function setConfig($name, $value)
    {
        $this->config[$name] = $value;

        return $this;
    }

    public function getPid($name = 'master')
    {
        $path = $this->getPath();
        if (!file_exists($path)) {
            return;
        }
        $pids = json_decode(file_get_contents($this->getPath()), true);

        return isset($pids[$name]) ? $pids[$name] : null;
    }

    public function setServer($protocol = 'http')
    {
        $this->server = ($protocol == 'http') ?
            new \swoole_http_server($this->host, $this->port) :
            new \swoole_server($this->host, $this->port);

        return $this;
    }

    public function getServer()
    {
        return $this->server;
    }

    public function onStart($serv)
    {
        swoole_set_process_name("{$this->name}: master process");
        $content = ['master' => $serv->master_pid, 'manager' => $serv->manager_pid];
        file_put_contents($this->getPath(), json_encode($content));
        \bsf\Log\Log::info('Master Start!');
    }

    public function onManagerStart()
    {
        swoole_set_process_name("{$this->name}: manager process");
        \bsf\Log\Log::info('Manager Start!');
    }

    public function onWorkerStart($serv, $worker_id)
    {
        if ($worker_id >= $serv->setting['worker_num']) {
            swoole_set_process_name("{$this->name}: task process");
            \bsf\Log\Log::info('Tasker Start!');
        } else {
            swoole_set_process_name("{$this->name}: worker process");
            \bsf\Log\Log::info('Worker Start!');
        }
    }

    public function onWorkerError($serv, $worker_id, $worker_pid, $exit_code)
    {
        \bsf\Log\Log::error('Worker Error!'."worker_id: $worker_id, exit_code: $exit_code");
    }

    public function onFinish()
    {
    }

    public function onShutdown()
    {
        @unlink($this->getPath());
    }

    public function prepare()
    {
        $this->server->set($this->config);

        $this->server->on('Start', [$this, 'onStart']);
        $this->server->on('ManagerStart', [$this, 'onManagerStart']);
        $this->server->on('WorkerStart', [$this, 'onWorkerStart']);
        $this->server->on('WorkerError', [$this, 'onWorkerError']);
        $this->server->on('Shutdown', [$this, 'onShutdown']);

        if ($this->server instanceof \swoole_http_server) {
            $foundation = new Foundation($this->server, static::getRouter());
            $this->server->on('Request', [$foundation, 'onRequest']);
        } else {
            $package = new Package(static::getRouter());
            $this->server->on('Receive', [$package, 'onReceive']);
        }

        $this->server->on('Finish', [$this, 'onFinish']);

        return $this;
    }

    public function start()
    {
        $this->server->start();
    }
}
