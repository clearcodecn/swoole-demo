<?php

/**
 * author : rookiejin <mrjnamei@gmail.com>
 * createTime : 2018/1/18 17:33
 * description: Task.php - swoole-demo
 */

class Task{
    public $generator ;
    protected $finished = false;
    public function __construct(Generator $generator)
    {
        $this->generator = $generator;
    }

    public function run()
    {
        $key = $this->generator->current();
        $result = Pool::getInstance()->getResult($key);
        if(!is_null($result)) {
            $this->generator->send($result);
            // 为了让任务结束，我们判断一下迭代器里面是否还有值
            if(!$this->generator->valid()) {
                $this->finished = true;
            }
        }
    }

    public function isFinished(){
        return $this->finished ;
    }
}
