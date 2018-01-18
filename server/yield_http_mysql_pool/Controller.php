<?php

/**
 * author : rookiejin <mrjnamei@gmail.com>
 * createTime : 2018/1/18 17:32
 * description: Controller.php - swoole-demo
 */
class Controller
{
    protected $context;

    public function getUserinfo()
    {
        $user    = yield Pool::getInstance()->query("select * from users where id = 1");
        $address = yield Pool::getInstance()->query("select * from users where id = 2");
        $address1 = yield Pool::getInstance()->query("select * from users where id = 2");
        $arr = [$user,$address,$address1];
        $this->context->response->end(json_encode($arr));
    }

    public function setContext(Context $context)
    {
        $this->context = $context;
    }
}
