<?php
namespace cathy\AsyncTask\main;
use cathy\AsyncTask\exception\ExecAsyncTaskException;

/**
 * 异步任务提供器
 */
class AsyncTaskProducer
{
    protected $address;
    public function __construct(string $host = '127.0.0.1', int $port = 19345)
    {
        $this->address = 'tcp://'.$host.':'.$port;
    }

    /**
     * @throws ExecAsyncTaskException
     */
    public function execute(AsyncTask $asyncTask): string
    {
        $data = $asyncTask->toJson();
        $client = stream_socket_client($this->address, $errCode, $errMsg, 10, STREAM_CLIENT_ASYNC_CONNECT|STREAM_CLIENT_CONNECT);
        if(strlen($data) == stream_socket_sendto($client, $data)){
            return $asyncTask->taskKey;
        }
        throw new ExecAsyncTaskException($errMsg, $errCode);
    }
}