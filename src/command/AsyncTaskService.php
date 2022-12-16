<?php
namespace cathy\AsyncTask\command;
use cathy\AsyncTask\main\AsyncTaskSynchronizer;
use Exception;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\facade\Cache;
use think\facade\Config;
use Workerman\Worker;
use function app;

class AsyncTaskService extends Command
{
    public function configure()
    {
        $this->setName('worker:task')
            ->addArgument('action', Argument::OPTIONAL, "start|stop|restart|reload|status|connections", 'start')
            ->setDescription('AsyncTask Server for ThinkPHP6');
    }

    public function execute(Input $input, Output $output)
    {
        $configs = include_once __DIR__.'/../config/config.php';
        $action = $input->getArgument('action');

        if (DIRECTORY_SEPARATOR !== '\\') {
            if (!in_array($action, ['start', 'stop', 'reload', 'restart', 'status', 'connections'])) {
                $output->writeln("Invalid argument action:{$action}, Expected start|stop|restart|reload|status|connections .");
                exit(1);
            }

            global $argv;
            array_shift($argv);
            array_shift($argv);
            array_unshift($argv, 'think', $action);
        }

        if ('start' == $action) {
            $output->writeln('Starting AsyncTask server...');
        }

        $option = Config::get('worker_task');

        if ($input->hasOption('host')) {
            $host = $input->getOption('host');
        } else {
            $host = !empty($option['host']) ? $option['host'] : $configs['host'];
        }

        if ($input->hasOption('port')) {
            $port = $input->getOption('port');
        } else {
            $port = !empty($option['port']) ? $option['port'] : $configs['port'];
        }
        if(empty($option['process_number'])){
            $option['process_number'] = $configs['process_number'];
        }
        if(empty($option['name'])){
            $option['name'] = $configs['name'];
        }
        if(empty($option['reuse_port'])){
            if(isset($option['reuse_port'])){
                $option['reuse_port'] = false;
            }else{
                $option['reuse_port'] = $configs['reuse_port'];
            }
        }
        if(empty($option['synchronizer_ttl'])){
            $option['synchronizer_ttl'] = $configs['synchronizer_ttl'];
        }
        if($input->hasOption('daemon')){
            $option['daemon'] = true;
        }

        $this->start($host, (int) $port, $option);
    }

    public function start(string $host, int $port, array $option = []){
        if(isset($option['daemon']) && $option['daemon']){
            Worker::$daemonize = true;
        }
        $task_worker = new Worker('tcp://'.$host.':'.$port);
        $task_worker->count = $option['process_number']; // 进程数
        $task_worker->name = $option['name']; // 名称
        $task_worker->reusePort = $option['reuse_port']; // 根据配置开启端口复用，让每一个任务进程平衡异步任务，仅php7支持
        $task_worker->onMessage = function($connection, $data) use ($option) {
            $data = json_decode($data,true);
            $taskClass = $data['taskClass'];
            $taskMethod = $data['taskMethod'];
            $taskData = $data['data'];
            $taskKey = $data['taskKey'];
            $taskSynchronizer = new AsyncTaskSynchronizer($taskKey, $option['synchronizer_ttl']);
            try{
                $class = is_object($taskClass) ?: app($taskClass);
                $class->$taskMethod($taskData);
            }catch (Exception $e){
                $taskSynchronizer->error($e->getMessage());
            }
            $connection->close();
        };
        $task_worker->run();
    }
}