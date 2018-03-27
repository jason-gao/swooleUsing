<?php
/**
 * Desc: https://wiki.swoole.com/wiki/page/397.html
 * Created by PhpStorm.
 * User: jason-gao
 * Date: 2018/3/22 13:49
 *
 * 1. swoole_websocket_server 继承自 swoole_http_server
 * https://github.com/swoole/swoole-src/blob/master/examples/websocket/server.php
 */


$server = new swoole_websocket_server("0.0.0.0", 9502);

$fdTimeIdMap = [];

$server->set([
    'pid_file'          => __DIR__ . '/../../pid/websocketServer.pid',
    'worker_num'        => 4,
    'max_request'       => 2000,
    'max_conn'          => 1024,
    //'daemonize'  => 1,
    'log_file'          => __DIR__ . '/../../log/websocketServer.log',
    'log_level'         => 5, //https://wiki.swoole.com/wiki/page/538.html  貌似没用啊
    //'open_cpu_affinity' => 1,
    'task_worker_num'   => 2,
    'open_cpu_affinity' => 1,
    //'enable_port_reuse' => true,
    'reactor_num'       => 4,
    //'dispatch_mode' => 3,
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
    echo "websocket server start\n";
    swoole_set_process_name('websocketservermaster');
});

$server->on('workerstart', function ($server, $id) {
    echo "websocket server on workstart\n";
    $flag = 'worker';
    if ($server->taskworker) {
        $flag = 'task';
    }
    swoole_set_process_name("  websocketserver$flag-$id");
});

$server->on('managerstart', function ($server) {
    echo "websocket server ManagerStart managerpid:{$server->manager_pid}\n";
    swoole_set_process_name("  websocketserverManager");
});

$server->on("open", function (swoole_websocket_server $server, $req) {
    echo "connection open:{$req->fd}\n";
//    $clientInfo = $server->connection_info($req->fd);

    /**
     * $clientInfo
     * Array
     * (
     * [websocket_status] => 3
     * [server_port] => 9502
     * [server_fd] => 4
     * [socket_type] => 1
     * [remote_port] => 50313
     * [remote_ip] => 192.168.5.118
     * [reactor_id] => 2
     * [connect_time] => 1521771556
     * [last_time] => 1521771557
     * [close_errno] => 0
     * )
     */
//    echo print_r($clientInfo, 1);
});


$server->on('message', function (swoole_websocket_server $server, $frame) {
    echo "receive from {$frame->fd}:{$frame->data},opcode:{$frame->opcode},fin:{$frame->finish}\n";

    //响应客户端hello world
    $server->push($frame->fd, json_encode(["hello", "world"]));

    //广播给其他人
//    foreach ($server->connections as $fd) {
//        if ($fd != $frame->fd) {
//            $server->push($fd, "from client:{$frame->fd}-" . $frame->data);
//        }
//    }

    //投递task
    $taskData = [
        'clientFd'   => $frame->fd,
        'clientData' => $frame->data
    ];
    $taskId   = $server->task($taskData, null); //0 - (serv->task_worker_num -1) https://wiki.swoole.com/wiki/page/134.html
    if ($taskId === false) {
        echo "task投递失败\n";
    } else {
        echo "投递任务到task进程#taskId:$taskId\n";
    }

    //启动定时器 定时向客户端推送数据
    $timeMs  = 5000;
    $_params = [
        'test' => 'test1'
    ];

    global $fdTimeIdMap;

    //先清除定时器
    clearTimer($frame->fd, 'onMessage');

    //新建定时器任务
    $fdTimeIdMap[$frame->fd][] = swoole_timer_tick($timeMs, function ($timerId) use ($server, $frame, $_params) {
        $jsonParams = json_encode($_params);
        echo "timeId:$timerId,params:$jsonParams\n";
        //客户端关闭连接后frame->fd不存在的情况处理
        if (fdValid($frame->fd, $server)) {
            $server->push($frame->fd, json_encode(['fd' => $frame->fd]));
        }
    });

});

//这个事件监听了，貌似onOpen就不会执行
//$server->on('HandShake', function (swoole_http_request $request, swoole_http_response $response) {
//    echo "handshake...\n";
//});


//websocket client请求貌似没有请求到这里
//$server->on('request', function ($request, $response) {
//    echo "websocket server request\n";
//    global $server;//调用外部的server
//    // $server->connections 遍历所有websocket连接用户的fd，给所有用户推送
//    foreach ($server->connections as $fd) {
//        $server->push($fd, $request->get['message']);
//    }
//});

$server->on('close', function (swoole_websocket_server $server, $fd) {
    echo "connection close:fd:{$fd},worker_id:{$server->worker_id}\n";

    //清除定时器
    clearTimer($fd, 'onClose');

});

$server->on('task', function (swoole_websocket_server $server, $task_id, $worker_id, $data) {

    //验证当前是task进程
    $task = '';
    if ($server->taskworker) {
        $task = "当前是task进程 ";
    }

    $jsonData = json_encode($data);
    echo "{$task}#server->task_worker_id={$server->worker_id}\tonTask: from_worker_id={$worker_id}, task_id=$task_id, data={$jsonData}\n";

    //广播给其他人
    $clientFd   = $data['clientFd'];
    $clientData = $data['clientData'];
    foreach ($server->connections as $fd) {
        if ($fd != $clientFd) {
            $server->push($fd, "from client:$clientFd-" . $clientData);
        }
    }

    $server->finish("task finished return\n");
});


$server->on('finish', function (swoole_websocket_server $server, $task_id, $taskReturn) {
    //验证当前是worker进程
    $task = '当前是worker进程';
    if ($server->taskworker) {
        $task = "当前是task进程 ";
    }

    echo "{$task}#from_worker_id={$server->worker_id}\tonFinish:task_id=$task_id\n";

    echo "taskReturn:{$taskReturn}\n";
});


function clearTimer($fd, $source = '')
{
    global $fdTimeIdMap;
    var_dump("#{$source}#clearTimer start");
    var_dump($fdTimeIdMap);

    if (isset($fdTimeIdMap[$fd])) {
        foreach ($fdTimeIdMap[$fd] as $key => $timerId) {
            echo "清除定时器:timeId:{$timerId}\n";
            if (swoole_timer_exists($timerId)) {
                swoole_timer_clear($timerId);
                unset($fdTimeIdMap[$fd][$key]);
            }
        }
    }

    var_dump("#{$source}#clearTimer end");
    var_dump($fdTimeIdMap);
}


function fdValid($_fd, $server)
{
    foreach ($server->connections as $fd) {
        if ($_fd == $fd) {
            return true;
        }
    }

    return false;
}


$server->start();