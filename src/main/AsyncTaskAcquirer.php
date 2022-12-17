<?php

namespace cathy\AsyncTask\main;

use Exception;
use think\facade\Cache;

/**
 * 异步任务获取器
 */
class AsyncTaskAcquirer
{
    /**
     * @throws Exception
     */
    public static function get($taskKey) {
        $taskKey = 'async_task_'.$taskKey;
        if (Cache::has($taskKey)) {
            throw new Exception('任务不存在');
        }
        return Cache::get($taskKey);
    }
}