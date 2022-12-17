# thinkphp6-async-task

## 介绍
基于workerman的thinkphp6异步任务模型

## 安装
~~~
composer require cathy/thinkphp6-async-task
~~~

## 使用
> 创建异步任务类及方法
~~~
<?php
namespace app\tasks;

use cathy\AsyncTask\main\AsyncTaskSynchronizer;

class DemoAsyncTasks
{
    public function demo($data, AsyncTaskSynchronizer $taskSynchronizer) {
        for($i = 1;$i <= 100; $i++) {
            $taskSynchronizer->update($i);
            sleep(1);
        }
        $taskSynchronizer->done(['message'=>'Hi!'.$data['name']]);
    }
}
~~~
> 执行异步任务
~~~
$asyncTaskProducer = new AsyncTaskProducer();
try {
    $taskKey = $asyncTaskProducer->execute(
        new AsyncTask(DemoAsyncTasks::class, 'demo', ['name'=>'cathy'])
    );
    echo $taskKey;
} catch (ExecAsyncTaskException|InvalidAsyncTaskException $e) {
    echo $e->getMessage();
}
~~~
> 获取任务进度
~~~
try {
    $taskKey = ''; // 执行异步任务得到的taskKey
    echo json_encode(AsyncTaskAcquirer::get($taskKey));
} catch (Exception $e) {
    echo $e->getMessage();
}
~~~