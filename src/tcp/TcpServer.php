<?php
/**
 * Desc: tcpserver
 * Created by PhpStorm.
 * User: jason-gao
 * Date: 2018/3/27 18:22
 */

$server = new swoole_server("0.0.0.0", 9503);
$server->set([
    'pid_file'          => __DIR__ . '/../../pid/tcpserver.pid',
    'worker_num'        => 4,
    'max_request'       => 2000,
    'max_conn'          => 1024,
    //'daemonize'  => 1,
    'log_file'          => __DIR__ . '/../../log/tcpserver.log',
    'log_level'         => 5, //https://wiki.swoole.com/wiki/page/538.html  貌似没用啊
    //'open_cpu_affinity' => 1,
    'task_worker_num'   => 2,
    'open_cpu_affinity' => 1,
    //'enable_port_reuse' => true,
    'reactor_num'       => 4,
//    'dispatch_mode' => 1,
    //'discard_timeout_request' => true,
    //'open_tcp_nodelay' => true,
    //'open_mqtt_protocol' => true,
    //'user' => 'www-data',
    //'group' => 'www-data',
    //'ssl_cert_file' => $key_dir.'/ssl.crt',
    //'ssl_key_file' => $key_dir.'/ssl.key',
//    'websocket_subprotocol' => 'chat'
]);


$server->on('start', function () {
    echo "tcp server start\n";
    swoole_set_process_name('tcpserverrmaster');
});

$server->on('workerstart', function ($server, $id) {
    echo "tcp server on workstart\n";
    $flag = 'worker';
    if ($server->taskworker) {
        $flag = 'task';
    }
    swoole_set_process_name("  tcpserver$flag-$id");
});

$server->on('managerstart', function ($server) {
    echo "tcp server ManagerStart managerpid:{$server->manager_pid}\n";
    swoole_set_process_name("  tcpserverManager");
});

$server->on('connect', function (swoole_server $server, $fd, $reactorId) {
    echo "connection open:fd:{$fd}:reactorId:{$reactorId}\n";
});

$server->on('receive', function (swoole_server $server, $fd, $reactorId, $data) {
    $server->send($fd, "Swoole {$data}\n");
//    $server->close($fd);
});

$server->on('task', function(swoole_server $server, $task_id, $worker_id, $data){
    //验证当前是task进程
    $task = '';
    if ($server->taskworker) {
        $task = "当前是task进程 ";
    }

    $jsonData = json_encode($data);
    echo "{$task}#server->task_worker_id={$server->worker_id}\tonTask: from_worker_id={$worker_id}, task_id=$task_id, data={$jsonData}\n";

});

$server->on('finish', function (swoole_server $server, $task_id, $taskReturn) {
    //验证当前是worker进程
    $task = '当前是worker进程';
    if ($server->taskworker) {
        $task = "当前是task进程 ";
    }

    echo "{$task}#from_worker_id={$server->worker_id}\tonFinish:task_id=$task_id\n";

    echo "taskReturn:{$taskReturn}\n";
});

$server->on('close', function (swoole_server $server, $fd) {
    echo "connnection close:fd:{$fd}\n";
});

$server->start();