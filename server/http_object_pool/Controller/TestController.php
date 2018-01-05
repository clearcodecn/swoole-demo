<?php

/**
 * author : rookiejin <mrjnamei@gmail.com>
 * createTime : 2018/1/5 15:12
 * description: TestController.php - swoole-demo
 */

/**
 * Created by PhpStorm.
 * User: rookiejin <mrjnamei@gmail.com>
 * Date: 2018/1/5
 * Time: 15:12
 * description: TestController.php - swoole-demo
 */

namespace Controller;


class TestController extends Controller
{
    public $poolName = TestController::class ;

    function init()
    {
        // todo 
    }

    /**
     * @router('/test/index')
     */
    public function index()
    {
        $template = <<<HTML
    <!doctype html>
    <html lang="en">
    <head>
    <meta charset="UTF-8">
                 <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
                             <meta http-equiv="X-UA-Compatible" content="ie=edge">
                 <title>Document</title>
    </head>
    <body>
        hello world ;
    </body>
    </html>
HTML;
        $this->context->send($template);
    }

}
