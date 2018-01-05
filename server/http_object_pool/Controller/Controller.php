<?php
/**
 * Created by PhpStorm.
 * User: rookiejin <mrjnamei@gmail.com>
 * Date: 2018/1/5
 * Time: 14:58
 * description: Controller.php - swoole-demo
 */
namespace Controller ;

abstract class Controller
{
    public $server ;

    /**
     * @var \Core\Context
     */
    public $context ;

    public $destory ;

    public function __construct()
    {
        $this->server = \Core\HttpServer::$_worker->server ;
    }

    public function setContext($context)
    {
        $this->context = $context ;
    }

    abstract function init();

    public function destory()
    {
        if($this->destory)
        {
            return ;
        }
        $this->destory = true ;
        $this->context = null ;
        $this->server = null ;
        return $this->init() ;
    }

}
