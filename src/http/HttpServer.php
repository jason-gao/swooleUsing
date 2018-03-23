<?php
/**
 * Desc: https://github.com/swoole/swoole-src#http-server
 * Created by PhpStorm.
 * User: jason-gao
 * Date: 2018/3/20 17:16
 */

/**
 * benchmark
 * centos6 vm |Mem 2G |cpu cores 2
 * ab -c10 -n50000 http://localhost:9501/
 *
 * This is ApacheBench, Version 2.3 <$Revision: 655654 $>
 * Copyright 1996 Adam Twiss, Zeus Technology Ltd, http://www.zeustech.net/
 * Licensed to The Apache Software Foundation, http://www.apache.org/
 *
 * Benchmarking localhost (be patient)
 * Completed 5000 requests
 * Completed 10000 requests
 * Completed 15000 requests
 * Completed 20000 requests
 * Completed 25000 requests
 * Completed 30000 requests
 * Completed 35000 requests
 * Completed 40000 requests
 * Completed 45000 requests
 * Completed 50000 requests
 * Finished 50000 requests
 *
 *
 * Server Software:        swoole-http-server
 * Server Hostname:        localhost
 * Server Port:            9501
 *
 * Document Path:          /
 * Document Length:        23 bytes
 *
 * Concurrency Level:      10
 * Time taken for tests:   4.814 seconds
 * Complete requests:      50000
 * Failed requests:        0
 * Write errors:           0
 * Total transferred:      8550000 bytes
 * HTML transferred:       1150000 bytes
 * Requests per second:    10385.40 [#/sec] (mean)
 * Time per request:       0.963 [ms] (mean)
 * Time per request:       0.096 [ms] (mean, across all concurrent requests)
 * Transfer rate:          1734.28 [Kbytes/sec] received
 *
 * Connection Times (ms)
 * min  mean[+/-sd] median   max
 * Connect:        0    0   0.4      0      37
 * Processing:     0    1   0.4      1      37
 * Waiting:        0    0   0.4      0      37
 * Total:          0    1   0.6      1      37
 *
 * Percentage of the requests served within a certain time (ms)
 * 50%      1
 * 66%      1
 * 75%      1
 * 80%      1
 * 90%      1
 * 95%      1
 * 98%      1
 * 99%      2
 * 100%     37 (longest request)
 */

$http = new swoole_http_server("0.0.0.0", 9501, SWOOLE_BASE);

//https://wiki.swoole.com/wiki/page/274.html
$http->set([
    'pid_file'              => __DIR__ . '/../../pid/httpServer.pid',
    'worker_num'            => 4,
    'max_request'           => 2000,
    'max_conn'              => 1024,
    //'daemonize'  => 1,
    'log_file'              => __DIR__ . '/../../log/httpServer.log',
    'log_level'             => 5, //https://wiki.swoole.com/wiki/page/538.html  貌似没用啊
    //'open_cpu_affinity' => 1,
    'task_worker_num'       => 2,
    'open_cpu_affinity'     => 1,
    //'enable_port_reuse' => true,
    'reactor_num'           => 4,
    //'dispatch_mode' => 3,
    //'discard_timeout_request' => true,
    //'open_tcp_nodelay' => true,
    //'open_mqtt_protocol' => true,
    //'user' => 'www-data',
    //'group' => 'www-data',
    //'ssl_cert_file' => $key_dir.'/ssl.crt',
    //'ssl_key_file' => $key_dir.'/ssl.key',
    'enable_static_handler' => true,
    'document_root'         => __DIR__ . '../public'  //貌似没啥用
]);

$http->on("start", function (Swoole\Http\Server $server) {
    echo "Swoole http server is started at http://0.0.0.0:9501\n";
    swoole_set_process_name('httpservermaster');
});

$http->on('workerstart', function ($server, $id) {
    echo "workerstart \n";
    $flag = 'worker';
    if ($server->taskworker) {
        $flag = 'task';
    }
    swoole_set_process_name("  httpserver$flag-$id");
});


//貌似没有触发此事件
$http->on('managerstart', function ($server) {
    echo "Swoole ManagerStart\n";
    swoole_set_process_name(' httpservermanager');
});


//https://wiki.swoole.com/wiki/page/p-worker.html
$http->on('request', function (Swoole\Http\Request $request, swoole_http_response $response) {
//    $response->header('Content-Type', 'text/html; charset=utf-8');
//    $response->header('Server', 'swoole-http-server-jasong');
//    $response->header('Last-Modified', 'Thu, 18 Jun 2015 10:24:27 GMT');
//    $response->header('E-Tag', md5_file(__FILE__));
//    $response->header('Accept-Ranges', 'bytes');

    //cookie test
    $response->cookie('test1', '1234', time() + 86400, '/');
    $response->cookie('test2', '5678', time() + 86400);

    //request_uri test
    if ($request->server['request_uri'] == '/test.txt') {
        $last_modified_time = filemtime(__DIR__ . '/../../public/test.txt');
        $etag               = md5_file(__DIR__ . '/../../public/test.txt');
        // always send headers
        $response->header("Last-Modified", gmdate("D, d M Y H:i:s", $last_modified_time) . " GMT");
        $response->header("Etag", $etag);
//        if (strtotime($request->header['if-modified-since']) == $last_modified_time or trim($request->header['if-none-match']) == $etag) {
//            $response->status(304);
//            $response->end();
//        } else {
        $response->sendfile(__DIR__ . '/../../public/test.txt');
//        }
        return;
    }

    //chrome request
    if ($request->server['request_uri'] == '/favicon.ico') {
        $response->status(404);
        $response->end();
        return;
    }

    $response->end("<h1>Hello World.</h1>");
});


$http->on('task', function () {
    echo "async task\n";
});


$http->on('finish', function () {
    echo "task finish";
});

$http->start();

