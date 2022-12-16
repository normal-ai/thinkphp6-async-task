<?php

namespace cathy\AsyncTask\main;

use think\facade\Cache;

/**
 * 异步任务同步器
 */
class AsyncTaskSynchronizer
{
    protected $taskKey;
    protected $done = false;
    protected $progress = 0;
    protected $data;
    protected $errorMsg;
    protected $ttl = 3600;

    public function __construct($taskKey, $ttl = 3600)
    {
        $this->taskKey = 'async_task_'.$taskKey;
        $this->ttl = $ttl;
        if (!Cache::has($this->taskKey)) {
            Cache::set($this->taskKey, $this->toArray(), $this->ttl);
        }
    }

    public function toArray() {
        return [
            'done'=>$this->done,
            'progress'=>$this->progress,
            'data'=>$this->data
        ];
    }

    public function update(int $progress) {
        $this->progress = $progress;
        Cache::set($this->taskKey, $this->toArray(), $this->ttl);
    }

    public function done($data) {
        $this->done = true;
        $this->progress = 100;
        $this->data = $data;
        Cache::set($this->taskKey, $this->toArray(), $this->ttl);
    }

    public function error(string $errorMsg) {
        $this->errorMsg = $errorMsg;
        Cache::set($this->taskKey, $this->toArray(), $this->ttl);
    }

    public function status() {
        return Cache::get($this->taskKey);
    }
}