<?php
/**
 * Desc: swoole info
 * Created by PhpStorm.
 * User: jason-gao
 * Date: 2018/3/21 15:36
 */

echo "\n+---------version:----------+\n";
echo swoole_version();
echo "\n+---------version:----------+\n";


echo "\n+---------local ip:----------+\n";
$ips =  swoole_get_local_ip();
print_r($ips);
echo "\n+---------local ip:----------+\n";


echo "\n+---------local mac:----------+\n";
$ips =  swoole_get_local_mac();
print_r($ips);
echo "\n+---------local mac:----------+\n";


echo "\n+---------cpu num:----------+\n";
echo swoole_cpu_num();
echo "\n+---------cpu num:----------+\n";

