<?php
/**
 * author : rookiejin <mrjnamei@gmail.com>
 * createTime : 2018/1/4 14:58
 * description: tcp_get_and_send.php - swoole-demo
 * 起3个tcp客户端分别实现单聊，群聊.
 */

swoole_timer_after(1000, function(){
    makeClientTom();
});
swoole_timer_after(1000, function(){
    makeClientJerry();
});
swoole_timer_after(1000, function(){
    makeClientMaster();
});

function makeClientTom(){
    $name = ['from' => 'tom', 'to' => 'jerry','message' => 'hello jerry'];
    $client = new swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);
    $client->on("connect", function(swoole_client $cli) use($name) {
        echo "#info :tom connect success\n";
        $cli->send(json_encode($name));
    });
// 连接上了.
    $client->on('receive', function($cli , $data){
        echo "tom received:" . $data . "\n";
    });

    $client->on("close", function(swoole_client $cli){
    });

    $client->on("error", function(swoole_client $cli){
        echo "Connection close\n";
    });
    $client->connect('127.0.0.1', 9501);
}

function makeClientJerry(){
    $name = ['from' => 'jerry', 'to' => 'tom','message' => 'hello tom'];
    $client = new swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);
    $client->on("connect", function(swoole_client $cli) {
        echo "#info :jerry connect success\n";
    });
// 连接上了.
    $client->on('receive', function($cli , $data) use($name){
        echo "jerry received:" . $data . "\n";
        $cli->send(json_encode($name));
    });

    $client->on("close", function(swoole_client $cli){
    });

    $client->on("error", function(swoole_client $cli){
        echo "Connection close\n";
    });
    $client->connect('127.0.0.1', 9501);
}
function makeClientMaster(){
    $name = ['from' => 'master', 'to' => 'all','message' => 'hello every one'];
    $client = new swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);
    $client->on("connect", function(swoole_client $cli) use($name) {
        echo "#info :master connect success\n";
        $cli->send(json_encode($name));
    });

// 连接上了.
    $client->on('receive', function($cli , $data){
        echo "master received:" . $data . "\n";
    });

    $client->on("close", function(swoole_client $cli){
    });

    $client->on("error", function(swoole_client $cli){
        echo "Connection close\n";
    });
    $client->connect('127.0.0.1', 9501);
}



