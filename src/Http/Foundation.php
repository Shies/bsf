<?php

namespace bsf\Http;

class Foundation
{
    protected $server;
    protected $router;
    protected $request;
    protected $response;

    public function __construct($server, $router)
    {
        $this->server = $server;
        $this->router = $router;
    }

    public function onRequest($request, $response)
    {
        $this->request = new Request($request, $this->server);
        $this->response = new Response($response);

        try {
            $data = $this->router->run($this->request, $this->response);
            \bsf\Log\Log::info($this->request->getRequestLog().' code:'.$data['code'].' ['.$this->request->getTimeCost().'ms]');
        } catch (\Exception $e) {
            $data['code'] = $e->getCode();
            $data['msg'] = $e->getMessage();
            \bsf\Log\Log::error($this->request->getRequestLog().' code:'.$data['code'].' [Exception]:'.$data['msg'].' ['.$this->request->getTimeCost().'ms]');
        }
        $this->response($data);
    }

    public function getRequest()
    {
        return $this->request;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function response($data)
    {
        $this->response->setHeader('Access-Control-Allow-Credentials', 'true');
        $this->response->setHeader('Access-Control-Allow-Origin', 'http://www.bilibili.com');

        if ($this->request->isJsonp()) {
            $callback = $this->request->get('callback');
            $script = $this->request->get('script');
            if ($callback) {
                $this->response->jsonp($data, $callback, $script);
            } else {
                $this->response->json($data);
            }
        } else {
            $this->response->json($data);
        }
    }
}
