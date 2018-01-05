<?php
/**
 * author : rookiejin <mrjnamei@gmail.com>
 * createTime : 2018/1/4 18:18
 * description: main.php - swoole-demo
 */

function loadCloass()
{
    $dirs = [__DIR__ . '/Controller/*.php' , __DIR__ . '/Model/*.php' , __DIR__ . '/Factory/*.php',  __DIR__ . '/Core/*.php'];
    foreach ($dirs as $dir)
    {
        foreach (glob($dir) as $file)
        {
            require_once $file ;
        }
    }
}
// constant
defined("__TEMP__") or define("__TEMP__" , dirname(__DIR__) . '/temp/');

loadCloass();

$config = ['server' => ['worker_num' => 4 , "task_worker_num" => "20" , "dispatch_mode" => 3 ] , 'host' => '0.0.0.0' , 'port' => 9501];
$server = new \Core\HttpServer($config);

\Core\HttpServer::main();
