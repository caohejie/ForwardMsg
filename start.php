<?php
/**
 * run with command
 * php start.php start
 */

ini_set('display_errors', 'on');

use Workerman\Worker;

// 检查扩展
if (!extension_loaded('pcntl')) {
    exit("Please install pcntl extension. See http://doc3.workerman.net/install/install.html\n");
}

if (!extension_loaded('posix')) {
    exit("Please install posix extension. See http://doc3.workerman.net/install/install.html\n");
}

// 标记是全局启动
define('GLOBAL_START', 1);

require_once __DIR__ . '/Workerman/Autoloader.php';

require_once './apps/start_Forward.php';

$zhuanfa = new Visit();

$zhuanfa->start();

