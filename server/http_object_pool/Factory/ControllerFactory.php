<?php
/**
 * Created by PhpStorm.
 * User: rookiejin <mrjnamei@gmail.com>
 * Date: 2018/1/5
 * Time: 14:42
 * description: ControllerFactory.php - swoole-demo
 */
namespace Factory;

class ControllerFactory
{
    /**
     * @var \Factory\ControllerFactory
     */
    public static $instance ;

    private $pool ;

    public function __construct( )
    {
        self::$instance = $this ;
    }

    public static function getInstance()
    {
        if(is_null(self::$instance))
        {
            new ControllerFactory();
        }
        return self::$instance;
    }

    /**
     * @param $class
     * @return \Controller\Controller
     */
    public function getController($class)
    {
        if (isset($this->pool[$class]))
        {
            if(count($this->pool[$class]) > 0)
            {
                $controller = $this->pool[$class]->shift();
                $controller->init();
                $controller->poolName = $class ;
                return $controller ;
            }
        }
        else{
            $this->pool [$class] = new \SplQueue();
        }
        if (!class_exists($class)){
            throw new \RuntimeException("class not found :: {$class}");
        }
        $object = new $class;
        return $object ;
    }

    /**
     *
     * @param $controller
     */
    public function giveBack($controller)
    {
        if ($controller->destory())
        {
            $this->pool[$controller->poolName]->push($controller);
        }
    }


    /**
     * 用于打印输出对象池的对象以及个数
     */
    public function count()
    {
        if(is_null($this->pool))
        {
            return ;
        }
        echo "========对象池计算开始=======" , "\n";
        echo "对象类型总数：" . count($this->pool) , "\n";
        foreach ($this->pool as $key => $object) {
            echo "对象：{$key} , " . "个数:" . count($object) . "\n";
        }
        echo "========对象池计算结束=======" , "\n";
    }

}
