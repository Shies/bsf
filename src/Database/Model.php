<?php

namespace bsf\Database;

class Model
{
    public function queryByConn($method, $args)
    {
        $conn = PDOClient::getConn();

        $call = [$conn, $method];
        if (!is_callable($call)) {
            return false;
        }
        $response = call_user_func_array($call, $args);

        $error = ($code = $conn->errorCode()) ? ['code' => $code, 'info' => $conn->errorInfo()] : ['code' => '', 'info' => ''];
        PDOClient::lastError($error);

        return $response;
    }

    public function queryByStmt($method, $args)
    {
        if (!isset($args['sql']) || !$args['sql']) {
            return false;
        }

        $stmt = PDOClient::getStmt($args['sql']);
        unset($args['sql']);

        $stmt->execute($args['params']);

        $error = ($code = $stmt->errorCode()) ? ['code' => $code, 'info' => $stmt->errorInfo()] : ['code' => '', 'info' => ''];
        PDOClient::lastError($error);

        $call = [$stmt, $method];
        if (!is_callable($call)) {
            return false;
        }

        unset($args['params']);

        return call_user_func_array($call, $args['options']);
    }
}
