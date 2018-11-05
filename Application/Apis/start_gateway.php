<?php
/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link http://www.workerman.net/
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */
use \Workerman\Worker;

use Cbworker\Core\Application;
use Workerman\Lib\Timer;

require_once __DIR__ . '/../../vendor/autoload.php';

// 心跳间隔50秒
define('HEARTBEAT_TIME', 60);

$worker = new Worker("http://0.0.0.0:7272");

$worker->count = 4;

$worker->onWorkerStart = function ($worker) {
  Application::getInstance()->initialize();
  if($worker->id === 0) {
    Timer::add(1, function() use ($worker) {
      $now_time = time();
      foreach ($worker->connections as $connection) {
        if(!isset($connection->lastMessageTime)) {
          $connection->lastMessageTime = $now_time;
          continue;
        }
        if($now_time - $connection->lastMessageTime > HEARTBEAT_TIME) {
          $connection->close();
        }
      }
    });
    Application::getInstance()->clearTask();
  }
};

$worker->onMessage = function ($connection, $data) {
  $connection->lastMessageTime = time();
  Application::getInstance()->init($connection, $data);
  Application::getInstance()->Run();
};
// 如果不是在根目录启动，则运行runAll方法
if (!defined('GLOBAL_START')) {
  Worker::runAll();
}
