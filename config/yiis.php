<?php

return [
    'listen_ip' => env('YIIS_LISTEN_IP', '127.0.0.1'),
    'listen_port' => env('YIIS_LISTEN_PORT', 5200),
    'socket_type' => env('YIIS_SOCKET_TYPE', 1),
    'enable_coroutine_runtime' => env('COROUTINE_ENV') === 'true',
    'max_coroutine_num' => env('MAX_COROUTINE_NUM', 3000),
    'server' => env('YIIS_SERVER', 'YiiS'),
    'handle_static' => env('YIIS_HANDLE_STATIC') === 'true',
    'yii_base_path' => env('YIIS_BASE_PATH', dirname(Yii::getAlias('@vendor'))),
    'inotify_reload' => [
        'enable' => env('YIIS_INOTIFY_RELOAD') === 'true',
        'watch_path' => env('YIIS_WORK_PATH'),
        'file_types' => ['.php'],
        'excluded_dirs' => [],
        'log' => true,
    ],
    'event_handlers' => [],
    'websocket' => [
        'enable' => false,
    ],
    'sockets' => [],
    'processes' => [
        //[
        //    'class'    => \App\Processes\TestProcess::class,
        //    'redirect' => false, // Whether redirect stdin/stdout, true or false
        //    'pipe'     => 0 // The type of pipeline, 0: no pipeline 1: SOCK_STREAM 2: SOCK_DGRAM
        //    'enable'   => true // Whether to enable, default true
        //],
    ],
    'timer' => [
        'enable' => env('YIIS_TIMER') === 'true',
        'jobs' => [
            // Enable LaravelScheduleJob to run `php artisan schedule:run` every 1 minute, replace Linux Crontab
            //\Hhxsv5\LaravelS\Illuminate\LaravelScheduleJob::class,
            // Two ways to configure parameters:
            // [\App\Jobs\XxxCronJob::class, [1000, true]], // Pass in parameters when registering
            // \App\Jobs\XxxCronJob::class, // Override the corresponding method to return the configuration
        ],
        'max_wait_time' => 5,
    ],
    'swoole_tables' => [],
    'cleaners' => [],
    'destroy_controllers' => [
        'enable' => false,
        'excluded_list' => [],
    ],
    'swoole' => [
        'daemonize' => env('YIIS_DAEMONIZE') === 'true',
        'dispatch_mode' => 2,
        'reactor_num' => env('YIIS_REACTOR_NUM', function_exists('swoole_cpu_num') ? swoole_cpu_num() * 2 : 4),
        'worker_num' => env('YIIS_WORKER_NUM', function_exists('swoole_cpu_num') ? swoole_cpu_num() * 2 : 8),
        'task_worker_num' => env('YIIS_TASK_WORKER_NUM', function_exists('swoole_cpu_num') ? swoole_cpu_num() * 2 : 8),
        'task_ipc_mode' => 1,
        'task_max_request' => env('YIIS_TASK_MAX_REQUEST', 8000),
        'task_tmpdir' => @is_writable('/dev/shm/') ? '/dev/shm' : '/tmp',
        'max_request' => env('YIIS_MAX_REQUEST', 8000),
        'open_tcp_nodelay' => true,
        'pid_file' => env('YIIS_WORK_PATH') . '/runtime/yiis.pid',
        'log_file' => env('YIIS_WORK_PATH') . '/runtime/' . sprintf('logs/swoole-%s.log', date('Y-m-d')),
        'log_level' => 4,
        'document_root' => env('YIIS_DOCS_PATH'),
        'buffer_output_size' => 2 * 1024 * 1024,
        'socket_buffer_size' => 128 * 1024 * 1024,
        'package_max_length' => 4 * 1024 * 1024,
        'reload_async' => true,
        'max_wait_time' => 30,
        'enable_reuse_port' => true,
        'enable_coroutine' => false,
        'http_compression' => false,
        'open_cpu_affinity' => true,
        'tcp_fastopen' => env('TCP_FASTOPEN') === 'true',

        // Slow log
        'request_slowlog_timeout' => 1,
        'request_slowlog_file' => env('YIIS_WORK_PATH') . '/runtime/' . sprintf('logs/slow-%s.log', date('Y-m-d')),
        'trace_event_worker' => true,

        /*
         * More settings of Swoole
         * @see https://wiki.swoole.com/#/server/setting  Chinese
         * @see https://www.swoole.co.uk/docs/modules/swoole-server/configuration  English
         */
    ],
];