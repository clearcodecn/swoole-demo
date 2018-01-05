<?php

/**
 * author : rookiejin <mrjnamei@gmail.com>
 * createTime : 2018/1/5 14:31
 * description: HttpServer.php - swoole-demo
 */

/**
 * Created by PhpStorm.
 * User: rookiejin <mrjnamei@gmail.com>
 * Date: 2018/1/5
 * Time: 14:31
 * description: HttpServer.php - swoole-demo
 */

namespace Core;


use Factory\ControllerFactory;

class HttpServer extends AbsHttpServer
{
    function onSwooleTask($server, $task_id, $src_worker_id, $data)
    {
    }

    function onSwoolePipeMessage($server, $src_worker_id, $message)
    {
    }

    function onSwooleWrokerError($server, $worker_id, $worker_pid, $exit_code, $signal)
    {
    }

    function onRequest($request, $response)
    {
        // 解析路由 .
        $router = trim($request->server ['request_uri'] , '/')  ;
        try{
            if($router == 'favicon.ico')
            {
                $response->end('');
                return ;
            }
            if($router == '' )
            {
                // todo 首页地址 .
                $response->end('index ...');
                return ;
            }
            $routerArr = explode('/' ,$router);
            if(count($routerArr) == 1)
            {
                $controller = ucfirst($routerArr [0]) ;
                $method = "index";
            }
            else{
                $controller = ucfirst($routerArr [0]);
                $method = $routerArr [1];
            }
            // todo 大于 2段 / 的地址
            // 组装controller & router .
            $controllerNamespace = "Controller\\{$controller}Controller";
            $controllerObject = ControllerFactory::getInstance()->getController($controllerNamespace);
            $controllerObject->setContext(new Context($request,$response,$controllerObject));
            if(!method_exists($controllerObject , $method))
            {
                throw new \RuntimeException("method not found :: {$controllerNamespace}::$method()");
            }
            $controllerObject->{$method}();
        }
        catch (\Exception $exception) {
            $response->status(500);
            $response->end('server error ::' . $exception->getMessage());
        }
        return ;
    }
}
