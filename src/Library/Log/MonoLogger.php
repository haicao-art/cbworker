<?php
# @Author: crababy
# @Date:   2018-11-06T11:51:26+08:00
# @Last modified by:   crababy
# @Last modified time: 2018-11-06T11:51:31+08:00
# @License: http://www.opensource.org/licenses/mit-license.php MIT License

namespace Cbworker\Library\Log;

use Cbworker\Core\Config\Config;
use Cbworker\Core\AbstractInterface\Singleton;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Formatter\JsonFormatter;

class MonoLogger {

  use Singleton;

  private static $_logger = null;

  private $_file = null;

  private function __construct() {
    self::$_logger = new Logger(Config::getConf('Log.Name', 'cbworker'));

    switch (Config::getConf('Log.type', 'daily')) {
      case 'daily':
        $this->_file = Config::getConf('Log.LOG_DIR') . DIRECTORY_SEPARATOR . date('Y-m-d') . '.log';
        break;
      case 'single':
        $this->_file = Config::getConf('Log.LOG_DIR') . DIRECTORY_SEPARATOR . Config::getConf('Log.Name', 'cbworker') . '.log';
        break;
    }
    $stream = new StreamHandler($this->_file, Config::getConf('Log.level', 100));
    if(Config::getConf('Log.format', false)) {
      $format = new JsonFormatter(Config::getConf('Log.format'));
      $stream->setFormatter($format);
    }
    self::$_logger->pushHandler($stream);
  }


  public static function logger($message, $context, $level = 'INFO')
  {
    switch ($level) {
      case 'INFO':
        self::$_logger->info($message, $context);
        break;
      case 'ERROR':
        self::$_logger->error($message, $context);
        break;
      case 'DEBUG':
        self::$_logger->debug($message, $context);
        break;
    }
  }

}
