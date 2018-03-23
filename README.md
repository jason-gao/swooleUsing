# version:
 * php7.2.1
 * swoole 2.1.1

* swoole ide helper
    * git clone https://github.com/swoole/ide-helper
    * php dump.php 根据你php安装的swoole扩展生成output目录
    * 将ide-helper目录添加到编辑器的include_path里
    * phpStrom:settings->languages & frameworks->php->include_path
    * 添加完后在编辑器里就有swoole相关函数参数等提示
    
* http server 设置为daemonize = 1，日志才会写到log_file，否则直接输出到终端
    
* 设置进程名字 https://wiki.swoole.com/wiki/page/125.html
    
* 跟踪调试：strace -f -p 58952

* 回调：https://wiki.swoole.com/wiki/page/p-worker.html
* server配置：https://wiki.swoole.com/wiki/page/274.html
    * http server还可以配置这些: https://wiki.swoole.com/wiki/page/620.html
* Reactor、Worker、TaskWorker的关系
    * https://wiki.swoole.com/wiki/page/163.html
    
    