<?php

namespace bsf\Tcp;

class Response
{
    protected $_serv;
    protected $_fd;

    public function __construct($serv, $fd)
    {
        $this->_serv = $serv;
        $this->_fd = $fd;
    }

    public function json($data)
    {
        if (!$data) {
            return false;
        }
        $this->_serv->send($this->_fd, json_encode($data));
    }
}
