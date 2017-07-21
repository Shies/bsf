<?php

namespace bsf\Tcp;

class Request
{
    protected $_serv;
    protected $_uri;

    public function __construct($serv, $uri)
    {
        $this->_serv = $serv;
        $this->_uri = $uri;
    }

    public function getServ()
    {
        return $this->_serv;
    }

    public function getPath()
    {
        return $this->_uri;
    }
}
