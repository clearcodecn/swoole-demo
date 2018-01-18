<?php

/**
 * author : rookiejin <mrjnamei@gmail.com>
 * createTime : 2018/1/16 14:32
 * description: Server.php - swoole-demo
 */

function load($file){
    foreach ($file as $val){
        require_once __DIR__ .'/' . $val . ".php";
    }
}

load(["Context","Controller","Pool","Scheduler","Task"]);
$scheduler = new Scheduler();
$server = new swoole_http_server("0.0.0.0", 9501);
$server->on('workerStart',function() use($scheduler) {
    Pool::getInstance()->init()->keepAlive();
    $scheduler->ticker();
});
$server->on("request",function($request,$response) use($scheduler) {
    $context = new Context($request,$response);
    $controller = new Controller();
    $controller->setContext($context);
    $result = $controller->getUserinfo();
    if($result instanceof Generator) {
        $task = new Task($result);
        $scheduler->newTask($task);
    }
});
$server->start();
