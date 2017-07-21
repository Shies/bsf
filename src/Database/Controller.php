<?php

namespace bsf\Database;

class Controller
{
    protected $_data;
    protected $_timeout = 0.5;
    protected $_fields = ['model', 'method', 'dbname', 'params'];

    public function with($name, $value)
    {
        if (in_array($name, ['model', 'method', 'dbname', 'params'])) {
            $this->_data[$name] = $value;
        }

        return $this;
    }

    public function __call($method, $args)
    {
        $request = array_shift($args);
        $response = array_shift($args);
        $response->json($this->with('method', $method)->with('params', $args)->call($request->getServ()));
    }

    public function call($serv)
    {
        foreach ($this->_fields as $field) {
            if (!isset($this->_data[$field]) || !$this->_data[$field]) {
                return false;
            }
        }
        $task_id = Dao::getFreeTask($this->_data['dbname']);

        if ($task_id === null) {
            \bsf\Log\Log::debug('No free task process');

            return false;
        }
        $response = $serv->taskwait($this->_data, $this->_timeout, $task_id);

        // free task id
        Dao::freeTask($this->_data['dbname'], $task_id);
        \bsf\Log\Log::debug('Response: '.json_encode($response));

        return $response;
    }

    public function setMethod($method)
    {
        $this->_data['method'] = $method;

        return $this;
    }

    public function setParams($params)
    {
        $this->_data['params'] = $params;

        return $this;
    }
}
