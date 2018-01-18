<?php

/**
 * author : rookiejin <mrjnamei@gmail.com>
 * createTime : 2018/1/18 17:33
 * description: Context.php - swoole-demo
 */
class Context{
    public $request;  // $request
    public $response; // $response
    public function __construct($request,$response){
        $this->request = $request;
        $this->response = $response;
    }
}
