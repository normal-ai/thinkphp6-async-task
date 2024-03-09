<?php
namespace cathy\AsyncTask\command;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\facade\Config;
use Workerman\Worker;

class WebsocketService extends Command
{
    protected $lastMtime;

    public function configure()
    {
        $this->setName('worker:websocket')
            ->addArgument('action', Argument::OPTIONAL, "start|stop|restart|reload|status|connections", 'start')
            ->setDescription('Websocket Server for ThinkPHP6');
    }

    public function execute(Input $input, Output $output)
    {
        $configs = include_once __DIR__.'/../config/websocket.php';
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
            $output->writeln('Starting Websocket server...');
        }

        $option = Config::get('worker_websocket');

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

        if(empty($option['name'])){
            $option['name'] = $configs['name'];
        }

        if(empty($option['reuse_port'])){
            $option['reuse_port'] = $configs['reuse_port'];
        }

        $this->start($host, (int) $port, $option);
    }

    public function start(string $host, int $port, array $option = []){
        if(isset($option['daemon']) && $option['daemon']){
            Worker::$daemonize = true;
        }
        $ws_worker = new Worker('websocket://'.$host.':'.$port);
        $ws_worker->name = $option['name']; // 名称
        $ws_worker->onConnect = function ($connection) {
            echo 'New connection:'.$connection->getRemoteIp();
        };
        $ws_worker->onMessage = function($connection, $data) use ($option) {
            $connection->send('Hello ' . $data);
        };
        $ws_worker->onClose = function ($connection) {
            echo "Connection closed";
        };
        Worker::runAll();
    }
}