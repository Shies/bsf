<?php

namespace bsf\Log;

class Log
{
    public static $file;
    public static $default_level;
    public static $process;
    public static $process_name;
    public static $path;

    const DEBUG = 100;
    const INFO = 75;
    const NOTICE = 50;
    const WARNING = 25;
    const ERROR = 10;
    const CRITICAL = 5;

    public static $level_map = [
        'DEBUG' => self::DEBUG,
        'INFO' => self::INFO,
        'NOTICE' => self::NOTICE,
        'WARNING' => self::WARNING,
        'ERROR' => self::ERROR,
        'CRITICAL' => self::CRITICAL,
    ];

    public static function configure($path, $process_name, $level = 'INFO')
    {
        $folder = substr($path, 0, strrpos($path, '/'));
        if (!file_exists($folder)) {
            mkdir($folder);
        }
        self::$path = $path;
        self::$default_level = self::getLevel($level);
        self::$process_name = $process_name;
    }

    public static function start()
    {
        self::$file = @fopen(self::$path, 'a+');
        self::$process = new \swoole_process([__CLASS__, 'onStart'], true);
        self::$process->start();
        self::setPID(self::$process->pid);
    }

    public static function onStart()
    {
        swoole_set_process_name(self::$process_name.': log process');
        while ($content = self::$process->read()) {
            fwrite(self::$file, $content);
        }
    }

    public static function close()
    {
        $pid = self::getPID();
        if ($pid) {
            \swoole_process::kill($pid);
            unlink(self::$path.'.pid');
        }
    }

    protected static function log($msg, $level = self::INFO, $module = null)
    {
        if ($level > self::$default_level) {
            return;
        }

        $t = microtime(true);
        $micro = sprintf('%06d', ($t - floor($t)) * 1000000);
        $time = date('Y-m-d H:i:s').'.'.$micro;
        $msg = str_replace("\t", '', $msg);
        $msg = str_replace("\n", '', $msg);

        $str_level = self::getStrLevel($level);
        if (isset($module)) {
            $module = str_replace(array("\n", "\t"), array('', ''), $module);
        }
        $line = "$time\t$str_level\t$msg\t$module\r\n";

        self::write(self::$file, $line);
    }

    protected static function write($file, $line)
    {
        self::$process->write($line);
//        fwrite($file, $line);
//        swoole_async_write($file, $line);
    }

    public static function debug($msg)
    {
        self::log($msg, self::DEBUG);
    }

    public static function info($msg)
    {
        self::log($msg, self::INFO);
    }

    public static function notice($msg)
    {
        self::log($msg, self::NOTICE);
    }

    public static function warning($msg)
    {
        self::log($msg, self::WARNING);
    }

    public static function error($msg)
    {
        self::log($msg, self::ERROR);
    }

    protected static function getLevel($str)
    {
        return isset(self::$level_map[$str]) ? self::$level_map[$str] : false;
    }

    protected static function getStrLevel($level)
    {
        $ret = '[Unknown]';
        switch ($level) {
            case self::DEBUG:
                $ret = '[DEBUG]';
                break;
            case self::INFO:
                $ret = '[INFO]';
                break;
            case self::NOTICE:
                $ret = '[NOTICE]';
                break;
            case self::WARNING:
                $ret = '[WARNING]';
                break;
            case self::ERROR:
                $ret = '[ERROR]';
                break;
            case self::CRITICAL:
                $ret = '[CRITICAL]';
                break;
        }

        return $ret;
    }

    private static function setPID($pid)
    {
        file_put_contents(self::$path.'.pid', $pid);
    }

    private static function getPID()
    {
        return file_get_contents(self::$path.'.pid');
    }
}
