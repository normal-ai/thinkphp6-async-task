<?php
namespace cathy\AsyncTask\command;
use cathy\AsyncTask\main\AsyncTaskSynchronizer;
use Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\facade\App;
use think\facade\Config;
use Workerman\Lib\Timer;
use Workerman\Worker;
use function app;

class AsyncTaskService extends Command
{
    protected $lastMtime;

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

        if($input->hasOption('daemon')){
            $option['daemon'] = true;
        } else {
            $option['daemon'] = !empty($option['daemon']) ? $option['daemon'] : $configs['daemon'];
        }

        if(empty($option['count'])){
            $option['count'] = $configs['count'];
        }

        if(empty($option['name'])){
            $option['name'] = $configs['name'];
        }

        if(empty($option['reuse_port'])){
            $option['reuse_port'] = $configs['reuse_port'];
        }

        if(empty($option['synchronizer_ttl'])){
            $option['synchronizer_ttl'] = $configs['synchronizer_ttl'];
        }

        if(empty($option['file_monitor'])){
            $option['file_monitor'] = $configs['file_monitor'];
        }

        if(empty($option['file_monitor_interval'])){
            $option['file_monitor_interval'] = $configs['file_monitor_interval'];
        }

        if(empty($option['file_monitor_path'])){
            $option['file_monitor_path'] = $configs['file_monitor_path'];
        }

        $this->start($host, (int) $port, $option);
    }

    public function start(string $host, int $port, array $option = []){
        if(isset($option['daemon']) && $option['daemon']){
            Worker::$daemonize = true;
        }
        $task_worker = new Worker('tcp://'.$host.':'.$port);
        $task_worker->count = $option['count']; // 进程数
        $task_worker->name = $option['name']; // 名称
        $task_worker->reusePort = $option['reuse_port']; // 根据配置开启端口复用，让每一个任务进程平衡异步任务，仅php7支持
        // 设置文件监控
        if (DIRECTORY_SEPARATOR !== '\\' && App::isDebug() && $option['file_monitor'] && 0 == $task_worker->id) {
            $timer = $option['file_monitor_interval'] ?: 2;
            $paths = !empty($option['file_monitor_path']) ? $option['file_monitor_path'] : [App::getAppPath(), App::getConfigPath()];
            Timer::add($timer, function () use ($paths) {
                foreach ($paths as $path) {
                    $dir      = new RecursiveDirectoryIterator($path);
                    $iterator = new RecursiveIteratorIterator($dir);
                    foreach ($iterator as $file) {
                        if (pathinfo($file, PATHINFO_EXTENSION) != 'php') {
                            continue;
                        }

                        if ($this->lastMtime < $file->getMTime()) {
                            echo '[update]' . $file . "\n";
                            posix_kill(posix_getppid(), SIGUSR1);
                            $this->lastMtime = $file->getMTime();
                            return;
                        }
                    }
                }
            });
        }
        $task_worker->onMessage = function($connection, $data) use ($option) {
            $data = json_decode($data,true);
            $taskClass = $data['taskClass'];
            $taskMethod = $data['taskMethod'];
            $taskData = $data['data'];
            $taskKey = $data['taskKey'];
            $taskSynchronizer = new AsyncTaskSynchronizer($taskKey, $option['synchronizer_ttl']);
            try{
                $class = is_object($taskClass) ?: app($taskClass);
                $class->$taskMethod($taskData, $taskSynchronizer);
            }catch (Exception $e){
                $taskSynchronizer->error($e->getMessage());
            }
            $connection->close();
        };
        Worker::runAll();
    }
}