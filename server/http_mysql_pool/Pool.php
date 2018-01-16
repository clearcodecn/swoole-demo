<?php

/**
 * author : rookiejin <mrjnamei@gmail.com>
 * createTime : 2018/1/16 14:32
 * description: Server.php - swoole-demo
 */
class Pool
{
    // 连接池数组 .
    protected $connections ;

    // 最大连接数
    protected $max ;

    // 最小连接数
    protected $min ;

    // 已连接数
    protected $count = 0 ;

    protected $inited = false ;

    // 单例
    private static $instance ;

    //数据库配置
    protected $config  = array(
        'host' => '127.0.0.1',
        'port' => 3306,
        'user' => 'root',
        'password' => 'rootpassword',
        'database' => 'swoole',
        'charset' => 'utf8',
        'timeout' => 2,
    );

    public function __construct()
    {
        //初始化连接是一个Spl队列
        $this->connections = new SplQueue() ;
        $this->max = 30 ;
        $this->min = 5 ;
        // 绑定单例
        self::$instance = & $this ;
    }

    // worker启动的时候 建立 min 个连接
    public function init()
    {
        if($this->inited){
            return ;
        }
        for($i = 0; $i < $this->min ; $i ++){
            $this->generate();
        }
        return $this ;
    }

    /**
     * 维持当前的连接数不断线，并且剔除断线的链接 .
     */
    public function keepAlive()
    {
        // 2分钟检测一次连接
        swoole_timer_tick( 1000 , function(){
            // 维持连接
            while ($this->connections->count() >0 && $next=$this->connections->shift()){
                $next->query("select 1" , function($db ,$res){
                    if($res == false){
                        return ;
                    }
                    echo "当前连接数：" . $this->connections->count() . PHP_EOL ;
                    $this->connections->push($db);
                });
            }
        });

        swoole_timer_tick(1000 , function(){
            // 维持活跃的链接数在 min-max之间
            if($this->connections->count() > $this->max) {
                while($this->max < $this->connections->count()){
                    $next = $this->connections->shift();
                    $next->close();
                    $this->count -- ;
                    echo "关闭连接...\n" ;
                }
            }
        });
    }

    // 建立一个新的连接
    public function generate($callback = null)
    {
        $db = new swoole_mysql ;
        $db->connect($this->config , function($db , $res) use($callback) {
            if($res == false){
                throw new Exception("数据库连接错误::" . $db->connect_errno . $db->connect_error);
            }
            $this->count ++ ;
            $this->addConnections($db);
            if(is_callable($callback)){
                call_user_func($callback);
            }
        });
    }

    // 连接推进队列
    public function addConnections($db)
    {
        $this->connections->push($db);
        return $this;
    }

    // 执行数据库命令 . 会判断连接数够不够，够就直接执行，不够就新建连接执行
    public function query($query , $callback)
    {
        if($this->connections->count() == 0) {
            $this->generate(function() use($query,$callback){
                $this->exec($query,$callback);
            });
        }
        else{
           $this->exec($query,$callback);
        }
    }
    // 直接执行数据库命令并且 callback();
    private function exec($query, $callback)
    {
        $db = $this->connections->shift();
        $db->query($query ,function($db , $result) use($callback){
            $this->connections->push($db);
            $callback($result);
        });
    }

    public static function getInstance()
    {
        if(is_null(self::$instance)){
            new Pool();
        }
        return self::$instance;
    }
}

$server = new swoole_http_server("0.0.0.0",9501);
$server->set([
    'worker_num' => 1 ,
]);
$server->on("WorkerStart",function($server , $wid){
    Pool::getInstance()->init()->keepAlive();
});
$server->on("request",function($request,$response){
    $pool = Pool::getInstance()->query("select * from users", function($res) use($response) {
        $response->end(json_encode($res));
    });
});
$server->start();
