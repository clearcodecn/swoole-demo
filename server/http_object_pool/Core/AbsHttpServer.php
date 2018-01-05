<?php

/**
 * author : rookiejin <mrjnamei@gmail.com>
 * createTime : 2018/1/5 14:25
 * description: Server.php - swoole-demo
 */
namespace Core;

use Factory\ControllerFactory;

abstract class AbsHttpServer {

    /**
     * @var \swoole_server
     */
    public $server ;

    /**
     * 配置项
     * @var $config array
     */
    public $config ;

    /**
     * @var \Server
     */
    public static $_worker ;

    /**
     * 存储pid文件的位置
     */
    public $pidFile ;

    /**
     * worker 进程的数量
     * @var $worker_num
     */
    public $worker_num;

    /**
     * 当前进程的worker_id
     * @var $worker_id
     */
    public $worker_id ;

    /**
     * task 进程数 + worker 进程数 = 总的服务进程
     * 给其他的进程发送消息:
     * for($i = 0 ; $i < $count ; $i ++) {
     *    if($i == $this->worker_id)  continue;表示是该进程
     *    $this->server->sendMessage($i , $data);
     * }
     * task 进程的数量
     * @var $task_num
     */
    public $task_num ;

    /**
     * Server constructor.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->server = new \swoole_http_server($config ['host'] , $config ['port']);
        $this->config = $config;
        $this->serverConfig();
        self::$_worker = & $this; // 引用
    }

    public function serverConfig()
    {
        $this->server->set($this->config['server']);
    }

    public function start()
    {
        // Server启动在主进程的主线程回调此函数
        $this->server->on("start",[$this , "onSwooleStart"]);
        // 此事件在Server正常结束时发生
        $this->server->on("shutDown", [$this , "onSwooleShutDown"]);
        //事件在Worker进程/Task进程启动时发生。这里创建的对象可以在进程生命周期内使用。
        $this->server->on("workerStart", [$this , "onSwooleWorkerStart"]);
        //  此事件在worker进程终止时发生。在此函数中可以回收worker进程申请的各类资源。
        $this->server->on("workerStop",[$this, "onSwooleWorkerStop"]);
        // worker 向task_worker进程投递任务触发
        $this->server->on("task", [$this, "onSwooleTask"]);
        // task_worker 返回值传给worker进程时触发
        $this->server->on("finish",[$this , "onSwooleFinish"]);
        // 当工作进程收到由 sendMessage 发送的管道消息时会触发onPipeMessage事件
        $this->server->on("pipeMessage",[$this ,"onSwoolePipeMessage"]);
        // 当worker/task_worker进程发生异常后会在Manager进程内回调此函数
        $this->server->on("workerError", [$this , "onSwooleWrokerError"]);
        // 当管理进程启动时调用它，函数原型：
        $this->server->on("managerStart", [$this , "onSwooleManagerStart"]);
        // onManagerStop
        $this->server->on("managerStop", [$this , "onSwooleManagerStop"]);
        // 请求来了触发
        $this->server->on("request",[$this , "onRequest"]);
        $this->server->start();
    }

    /**
     * @warning 进程隔离
     * 该步骤一般用于存储进程的 master_pid 和 manager_pid 到文件中
     * 本例子存储的位置是 __DIR__ . "/tmp/" 下面
     * 可以用 kill -15 master_pid 发送信号给进程关闭服务器，并且触发下面的onSwooleShutDown事件
     * @param $server
     */
    public function onSwooleStart($server)
    {
        $this->setProcessName('SwooleMaster');
        $debug = debug_backtrace();
        $this->pidFile = __TEMP__ . str_replace("/" , "_" , $debug[count($debug) - 1] ["file"] . ".pid" );
        $pid = [$server->master_pid , $server->manager_pid];
        file_put_contents($this->pidFile , implode(",", $pid));
    }

    /**
     * @param $server
     * 已关闭所有Reactor线程、HeartbeatCheck线程、UdpRecv线程
     * 已关闭所有Worker进程、Task进程、User进程
     * 已close所有TCP/UDP/UnixSocket监听端口
     * 已关闭主Reactor
     * @warning
     * 强制kill进程不会回调onShutdown，如kill -9
     * 需要使用kill -15来发送SIGTREM信号到主进程才能按照正常的流程终止
     * 在命令行中使用Ctrl+C中断程序会立即停止，底层不会回调onShutdown
     */
    public function onSwooleShutDown($server)
    {
        echo "shutdown\n";
    }

    /**
     * @warning 进程隔离
     * 该函数具有进程隔离性 ,
     * {$this} 对象从 swoole_server->start() 开始前设置的属性全部继承
     * {$this} 对象在 onSwooleStart,onSwooleManagerStart中设置的对象属于不同的进程中.
     * 因此这里的pidFile虽然在onSwooleStart中设置了，但是是不同的进程，所以找不到该值.
     * @param \swoole_server $server
     * @param int            $worker_id
     */
    public function onSwooleWorkerStart(\swoole_server $server, int $worker_id)
    {
        if($this->isTaskProcess($server))
        {
            $this->setProcessName('SwooleTask');
        }
        else{
            $this->setProcessName('SwooleWorker');
        }
        $debug = debug_backtrace();
        $this->pidFile = __TEMP__. str_replace("/" , "_" , $debug[count($debug) - 1] ["file"] . ".pid" );
        file_put_contents($this->pidFile , ",{$worker_id}" , FILE_APPEND);
        // 向下 兼容.
        static::$_worker = & $this ;

        // 这里起一个timer 每5秒计算对象池的对象个数以及对象名并且打印在屏幕上
        swoole_timer_tick(5 * 1000 , function(){
            ControllerFactory::getInstance()->count();
        });
    }

    public function onSwooleWorkerStop($server,$worker_id)
    {
        echo "#worker exited {$worker_id}\n";
    }

    /**
     * @warning 进程隔离 在task_worker进程内被调用
     * worker进程可以使用swoole_server_task函数向task_worker进程投递新的任务
     * $task_id和$src_worker_id组合起来才是全局唯一的，不同的worker进程投递的任务ID可能会有相同
     * 函数执行时遇到致命错误退出，或者被外部进程强制kill，当前的任务会被丢弃，但不会影响其他正在排队的Task
     * @param $server
     * @param $task_id 是任务ID 由swoole扩展内自动生成，用于区分不同的任务
     * @param $src_worker_id 来自于哪个worker进程
     * @param $data 是任务的内容
     * @return mixed $data
     */
    abstract function onSwooleTask($server , $task_id, $src_worker_id,$data) ;

    public function onSwooleFinish(){}

    /**
     * 当工作进程收到由 sendMessage 发送的管道消息时会触发onPipeMessage事件。worker/task进程都可能会触发onPipeMessage事件。
     * @param $server
     * @param $src_worker_id 消息来自哪个Worker进程
     * @param $message 消息内容，可以是任意PHP类型
     */
    abstract function onSwoolePipeMessage($server , $src_worker_id,$message) ;

    /**
     * worker进程发送错误的错误处理回调 .
     * 记录日志等操作
     * 此函数主要用于报警和监控，一旦发现Worker进程异常退出，那么很有可能是遇到了致命错误或者进程CoreDump。通过记录日志或者发送报警的信息来提示开发者进行相应的处理。
     * @param $server
     * @param $worker_id 是异常进程的编号
     * @param $worker_pid  是异常进程的ID
     * @param $exit_code  退出的状态码，范围是 1 ～255
     * @param $signal 进程退出的信号
     */
    abstract function onSwooleWrokerError($server ,$worker_id,$worker_pid,$exit_code,$signal);

    /**
     *
     */
    public function onSwooleManagerStart()
    {
        $this->setProcessName('SwooleManager');
    }

    /**
     * @param $server
     */
    public function onSwooleManagerStop($server)
    {
        echo "#managerstop\n";
    }

    public function setProcessName($name)
    {
        if(function_exists('cli_set_process_title'))
        {
            @cli_set_process_title($name);
        }
        else{
            @swoole_set_process_name($name);
        }
    }

    /**
     * 返回真说明该进程是task进程
     * @param $server
     * @return bool
     */
    public function isTaskProcess($server)
    {
        return $server->taskworker === true ;
    }

    /**
     * @param $request
     * @param $response
     */
    abstract function onRequest($request , $response) ;

    /**
     * main 运行入口方法
     */
    public static function main()
    {
        self::$_worker->start();
    }
}

