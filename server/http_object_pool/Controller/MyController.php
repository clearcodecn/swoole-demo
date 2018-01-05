<?php

/**
 * author : rookiejin <mrjnamei@gmail.com>
 * createTime : 2018/1/5 16:01
 * description: MyController.php - swoole-demo
 */
namespace Controller;


class MyController extends Controller
{
    function init()
    {
        // TODO: Implement init() method.
    }

    public function index()
    {
        $this->context->send(json_encode(['name' => '张三', 'sex' => '男']));
    }

}
