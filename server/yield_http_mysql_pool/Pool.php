<?php

/**
 * author : rookiejin <mrjnamei@gmail.com>
 * createTime : 2018/1/18 17:34
 * description: Pool.php - swoole-demo
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

    protected $results = [] ;

    protected $resultKey = 0 ;


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
        swoole_timer_tick( 1 * 300 , function(){
            // 维持连接
            while ($this->connections->count() >0 && $next=$this->connections->shift()){
                $next->query("select 1" , function($db ,$res){
                    if($res == false){
                        return ;
                    }
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
    public function generate()
    {
        $db = new swoole_mysql ;
        $db->connect($this->config , function($db , $res) {
            if($res == false){
                throw new Exception("数据库连接错误::" . $db->connect_errno . $db->connect_error);
            }
            $this->count ++ ;
            $this->addConnections($db);
        });
    }

    // 连接推进队列
    public function addConnections($db)
    {
        $this->connections->push($db);
        return $this;
    }

    public function query($query)
    {
        $key = $this->resultKey ++ ;
        if($this->connections->count() == 0)
        {
            $db = new swoole_mysql ;
            $db->connect($this->config , function($db , $res) use($query,$key) {
                if($res == false){
                    throw new Exception("数据库连接错误::" . $db->connect_errno . $db->connect_error);
                }
                $this->count ++ ;
                $db->query($query ,function($db , $result) use($key){
                    $this->addConnections($db);
                    $this->results [$key] = $result ;
                });
            });
        }
        else{
            $db = $this->connections->shift();
            $db->query($query ,function($db , $result) use($key){
                $this->addConnections($db);
                $this->results [$key] = $result ;
            });
        }
        return $key ;
    }

    public function getResult($key)
    {
        if (isset($this->results [ $key ])) {
            return $this->results [ $key ];
        }

        return null;
    }

    public static function getInstance()
    {
        if(is_null(self::$instance)){
            new Pool();
        }
        return self::$instance;
    }
}
