<?php

namespace bsf\Http;

class Response
{
    public $head;

    protected $_response;

    public function __construct($response)
    {
        $this->_response = $response;
    }

    public function __call($method, $args)
    {
        if (is_callable([$this->_response, $method])) {
            return call_user_func_array([$this->_response, $method], $args);
        }

        throw new \Exception("call to undefined method bsf\Http\Response::{$method}()");
    }

    public function setHeader($name, $value)
    {
        $this->head[$name] = $value;

        return $this;
    }

    public function addHeader($headers)
    {
        $this->head = array_merge($this->head, $headers);

        return $this;
    }

    public function resource($type, $path)
    {
        $this->_response->header('Content-Type', $type);

        $head = $this->head;
        unset($head['Content-Type']);
        foreach ($head as $key => $value) {
            $this->_response->header($key, $value);
        }
        $this->_response->sendfile($path);
    }

    public function redirect($url)
    {
        $this->_response->status(302);
        $this->_response->header('Location', $url);
        $this->_response->end();
    }

    public function response($data, $callback = null)
    {
        if ($callback) {
            $this->jsonp($data, $callback);
        } else {
            $this->json($data);
        }
    }

    public function json($data)
    {
        $this->head['Content-Type'] = 'application/json; charset=utf-8';
        foreach ($this->head as $key => $value) {
            $this->_response->header($key, $value);
        }
        $this->_response->end(json_encode($data));
    }

    public function jsonp($data, $callback, $script = '')
    {
        $this->head['Content-Type'] = 'application/json; charset=utf-8';
        foreach ($this->head as $key => $value) {
            $this->_response->header($key, $value);
        }
        $response = $callback.'('.json_encode($data).')';
        if ($script == 'script') {
            $this->head['Content-Type'] = 'text/html; charset=utf-8';
            $response = "<script type=\"text/javascript\">
                        document.domain = 'bilibili.com';
						window.parent".$response.
                '</script>';
        }

        $this->_response->end($response);
    }
}
