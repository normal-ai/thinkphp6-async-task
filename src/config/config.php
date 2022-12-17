<?php
return [
    'host'                  => '0.0.0.0',
    'port'                  => 19345,
    'count'                 => 4,
    'name'                  => 'AsyncTask',
    'daemon'                => true, // 是否开启守护进程
    'reuse_port'            => true, // 是否开启端口复用
    'synchronizer_ttl'      => 3600, // 任务同步器taskKey缓存时间
    'file_monitor'          => true, // 是否开启PHP文件更改监控（调试模式下自动开启）
    'file_monitor_interval' => 2,    // 文件监控检测时间间隔（秒）
    'file_monitor_path'     => [],   // 文件监控目录 默认监控application和config目录
];