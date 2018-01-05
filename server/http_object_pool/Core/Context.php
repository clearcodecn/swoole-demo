<?php
/**
 * Created by PhpStorm.
 * User: rookiejin <mrjnamei@gmail.com>
 * Date: 2018/1/5
 * Time: 15:04
 * description: Context.php - swoole-demo
 */

namespace Core;


use Factory\ControllerFactory;

class Context
{
    private $request ;

    private $response ;

    private $hasOver ;

    private $controller ;

    public function __construct($request , $response,$controller)
    {
        $this->request = $request ;
        $this->response = $response ;
        $this->hasOver = false ;
        $this->controller = $controller ;
    }

    public function send($string)
    {
        if($this->over())
        {
            return ;
        }
        $this->setResponseStatus(200);
        $this->hasOver = true ;
        $this->response->end($string);
        ControllerFactory::getInstance()->giveBack($this->controller);
    }

    public function setResponseStatus(int $status)
    {
        if($this->over())
        {
            return ;
        }
        $this->response->status($status);
    }

    public function setResponseHeader($key,$value)
    {
        if($this->over())
        {
            return ;
        }
        $this->response->header($key , $value);
    }

    public function over()
    {
        return $this->hasOver ;
    }

}
