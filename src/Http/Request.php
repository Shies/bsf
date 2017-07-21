<?php

namespace bsf\Http;

class Request
{
    protected $_request;
    protected $_serv;
    protected $_start_time;
    protected $_info;

    public function __construct($request, $server)
    {
        $this->_request = $request;
        $this->_serv = $server;
        $this->_start_time = round(microtime(true) * 1000);
    }

    public function getServ()
    {
        return $this->_serv;
    }

    public function getHeader($name = '')
    {
        return $name ? $this->_request->header[$name] : $this->_request->header;
    }

    public function getServer($name = '')
    {
        return $name ? $this->_request->server[$name] : $this->_request->server;
    }

    public function get($name = '')
    {
        if ($name) {
            return isset($this->_request->get[$name]) ? $this->_request->get[$name] : '';
        } else {
            return $this->_request->get;
        }
    }

    public function post($name = '')
    {
        if ($name) {
            return isset($this->_request->post[$name]) ? $this->_request->post[$name] : '';
        } else {
            return $this->_request->post;
        }
    }

    public function cookie($name = '')
    {
        return $name ? $this->_request->cookie[$name] : $this->_request->cookie;
    }

    public function files($name)
    {
        return $this->_request->files[$name];
    }

    public function getMethod()
    {
        return strtolower($this->_request->server['request_method']);
    }

    public function isAjax()
    {
        return isset($this->_request->server['http_x_request_with']) && $this->_request->server['http_x_requested_with'] == 'XMLHttpRequest';
    }

    public function isJsonp()
    {
        return $this->get('jsonp') == 'jsonp';
    }

    public function getRequestUri()
    {
        return $this->_request->server['request_uri'];
    }

    public function getQueryString()
    {
        return $this->_request->server['query_string'];
    }

    public function getTimeCost()
    {
        return round(microtime(true) * 1000) - $this->_start_time;
    }

    public function getRequestLog()
    {
        if ($this->getQueryString()) {
            $full_uri = $this->getRequestUri().'?'.$this->getQueryString();
        } else {
            $full_uri = $this->getRequestUri();
        }
        $log = $this->getServer('remote_addr').' '.strtoupper($this->getMethod()).' '.$this->getServer('server_protocol').' '.$full_uri;

        return $log;
    }

    public function setInfo($key, $value)
    {
        if (!is_array($this->_info)) {
            $this->_info = [];
        }
        $this->_info[$key] = $value;
    }

    public function getInfo($key)
    {
        return isset($this->_info[$key]) ? $this->_info[$key] : '';
    }
}
