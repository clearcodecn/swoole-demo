<?php

/**
 * author : rookiejin <mrjnamei@gmail.com>
 * createTime : 2018/1/18 17:33
 * description: Scheduler.php - swoole-demo
 */
class Scheduler{
    protected $taskList = [];
    protected $tickerTime = 1 ;
    public function newTask(Task $task)
    {
        $this->taskList [] = $task ;
    }

    public function ticker()
    {
        swoole_timer_tick($this->tickerTime, function($timerId){
            foreach ($this->taskList as $key => $task)
            {
                if($task->isFinished()){
                    unset($this->taskList[$key]);
                }
                $task->run();
            }
        });
    }
}
