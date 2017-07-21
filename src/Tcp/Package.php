<?php

namespace bsf\Tcp;

class Package
{
    protected $router;
    protected $request;
    protected $response;

    public function __construct($router)
    {
        $this->router = $router;
    }

    public function onReceive($serv, $fd, $from_id, $uri)
    {
        $this->request = new Request($serv, $uri);
        $this->response = new Response($serv, $fd);

        $this->router->run($this->request, $this->response);
    }
}
