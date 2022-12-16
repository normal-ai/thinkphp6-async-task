<?php

namespace cathy\AsyncTask\main;

use cathy\AsyncTask\exception\InvalidAsyncTaskException;

class AsyncTask
{
    protected $taskClass;
    protected $taskMethod;
    protected $data;
    public $taskKey;

    /**
     * @throws InvalidAsyncTaskException
     */
    public function __construct($taskClass, $taskMethod, $data, string $taskKey = null)
    {
        if(empty($taskClass)){
            throw new InvalidAsyncTaskException('Invalid TaskClass.');
        }
        if(is_string($taskClass)){
            if(!method_exists(app($taskClass),$taskMethod)){
                throw new InvalidAsyncTaskException('Invalid TaskMethod.');
            }
        }elseif(is_object($taskClass)){
            if(!method_exists($taskClass,$taskMethod)){
                throw new InvalidAsyncTaskException('Invalid TaskMethod.');
            }
        }else{
            throw new InvalidAsyncTaskException('Invalid TaskClass.');
        }
        $this->taskClass = $taskClass;
        $this->taskMethod = $taskMethod;
        $this->data = $data;
        $this->taskKey = $taskKey ?: uniqid();
    }

    public function toArray(): array
    {
        return [
            'taskClass'=>$this->taskClass,
            'taskMethod'=>$this->taskMethod,
            'taskKey'=>$this->taskKey,
            'data'=>$this->data
        ];
    }

    public function toJson() {
        return json_encode($this->toArray());
    }
}