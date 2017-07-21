<?php

$client = new swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC); //异步非阻塞

$client->on('connect', function ($cli) {
    $data = '/archive/4';
    $cli->send($data);
});

$client->on('receive', function ($cli, $data = '') {
    if (empty($data)) {
        $cli->close();
    } else {
        echo "received: $data\n";
        $cli->close();
    }
});

$client->on('close', function ($cli) {
    echo "close\n";
});

$client->on('error', function ($cli) {
    exit("error\n");
});

$client->connect('127.0.0.1', 9502, 0.5);
