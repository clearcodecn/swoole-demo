<?php

// 创建一个tcp服务器

$server = new swoole_server("127.0.0.1", 9501);

/**
 * @var $server swoole_server
 * @var $fd int 文件描述符
 */
$server->on("connect", function($server , $fd){
    echo "a client connected\n" ;
});

/**
 * @var $server swoole_server
 * @var $fd int 文件描述符
 * @var $from_id worker_id worker进程id
 * @var $data 接受的数据
 */
$server->on("receive", function($server , $fd , $from_id ,$data){
    echo "#server received msg:" , $data , "\n";
    $server->send($fd , "i received");
});

/**
 * @var $server swoole_server
 * @var $fd 文件描述符
 */
$server->on("close",function($server, $fd){
    echo "# client closed\n";
});
// 启动服务器
$server->start();
