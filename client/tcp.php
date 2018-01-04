<?php

$client = new swoole_client(SWOOLE_SOCK_TCP);

if(!$client->connect("127.0.0.1", 9501, -1)){
    exit("connect failed" . $client->errCode . "\n");
}

$client->send("helloworld");
echo $client->recv() , "\n";
$client->close();
