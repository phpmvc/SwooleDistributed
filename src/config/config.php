<?php
/**
 * Created by PhpStorm.
 * User: tmtbe
 * Date: 16-7-14
 * Time: 下午1:58
 */
/**
 * http服务器设置
 */
$config['http_server']['socket'] = '0.0.0.0';
$config['http_server']['port'] = 8081;
/**
 * 服务器设置
 */
$config['server']['socket'] = '0.0.0.0';
$config['server']['port'] = 9093;
$config['server']['dispatch_port'] = 9991;
$config['server']['name'] = 'SwooleServer';
$config['server']['send_use_task_num'] = 20;
$config['server']['log_path'] = '/../../';
$config['server']['log_max_files'] = 15;
$config['server']['log_level'] = \Monolog\Logger::DEBUG;
$config['server']['pack_tool'] = 'ProtoPack';
$config['server']['route_tool'] = 'NormalRoute';
$config['server']['set'] = [
    'reactor_num' => 2, //reactor thread num
    'worker_num' => 4,    //worker process num
    'backlog' => 128,   //listen backlog
    'open_tcp_nodelay' => 1,
    'dispatch_mode' => 5,
    'task_worker_num' => 5,
    'enable_reuse_port' => true,
];

/**
 * dispatch服务器设置
 */
$config['dispatch_server']['socket'] = '0.0.0.0';
$config['dispatch_server']['port'] = 60000;
$config['dispatch_server']['name'] = 'SwooleDispatch';
$config['dispatch_server']['password'] = 'Hello Dsipatch';
$config['dispatch_server']['set'] = [
    'reactor_num' => 2, //reactor thread num
    'worker_num' => 4,    //worker process num
    'backlog' => 128,   //listen backlog
    'open_tcp_nodelay' => 1,
    'dispatch_mode' => 3,
    'enable_reuse_port' => true,
];

//主从redis提高读的速度
//启动这个服务一定确保dispatch服务器上一定有一个redis只读服务器
$config['dispatch_server']['redis_slave'] = ['unix:/var/run/redis/redis.sock',0];

//异步服务是否启动一个新进程（启动后异步效率会降低2倍，但维护连接池只有一个）
$config['asyn_process_enable'] = false;
return $config;