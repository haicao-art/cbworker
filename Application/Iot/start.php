<?php
# @Author: crababy
# @Date:   2018-04-04T09:29:12+08:00
# @Last modified by:   crababy
# @Last modified time: 2018-04-04T09:29:23+08:00
# @License: http://www.opensource.org/licenses/mit-license.php MIT License


require_once '../../vendor/autoload.php';

use Workerman\Worker;
use \Workerman\WebServer;
use \WorkerMan\Lib\Timer;
use Cbworker\Core\Application;
use Cbworker\Library\Helper;

// 标记是全局启动
define('GLOBAL_START', 1);
// 心跳间隔50秒
define('HEARTBEAT_TIME', 60);

define('ROOT_PATH', __DIR__);
// WebServer
//Worker::$eventLoopClass
$worker = new Worker("http://0.0.0.0:55756");
$worker->name = 'CrababyFrameworkForWeb';
$worker->count = 4;

$worker->onWorkerStart = function($worker) {

  $app = Application::getInstance($worker);

  $worker->onConnect = function($connection) {

  };

  $worker->onMessage = function($connection, $data) use ($app) {
    $url_info = parse_url($_SERVER['REQUEST_URI']);
    if($url_info['path'] == '/favicon.ico') {
      $connection->send('');
      return;
    }
    $connection->lastMessageTime = time();
    $app->run($connection);
  };

  $worker->onWorkerStop = function() {
    echo 'onWorkerStop';
  };
};

Worker::runAll();
