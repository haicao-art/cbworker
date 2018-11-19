<?php
# @Author: crababy
# @Date:   2018-06-22T16:18:17+08:00
# @Last modified by:   crababy
# @Last modified time: 2018-06-22T16:18:23+08:00
# @License: http://www.opensource.org/licenses/mit-license.php MIT License
namespace Cbworker\Library;

use Cbworker\Core\AbstractInterface\Singleton;
use Cbworker\Core\Config\Config;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Monolog\Processor\MemoryPeakUsageProcessor;
use Monolog\Processor\MemoryUsageProcessor;

class MLogger
{
  use Singleton;

  public static $_loggerId;
  private static $_logger;
  private $_file;

  private function __construct()
  {
    self::$_logger = new Logger(Config::getConf('Log.Name', 'cbworker'));
    $this->_file = Config::getConf('Log.LOG_DIR') . DIRECTORY_SEPARATOR . Config::getConf('Log.Name', 'cbworker') . '.' . Config::getConf('Log.suffix', 'log');
    $stream = new RotatingFileHandler($this->_file, Config::getConf('Log.maxFiles', 15), Config::getConf('Log.level', 100));
    $formatter = Config::getConf('Log.formatter', 'Monolog\Formatter\JsonFormatter');
    $format = new $formatter();
    $stream->setFormatter($format);
    self::$_logger->pushHandler($stream);

    self::$_logger->pushProcessor(new MemoryPeakUsageProcessor());
    self::$_logger->pushProcessor(new MemoryUsageProcessor());

    self::pushLoggerId();
  }

  public static function pushLoggerId() {
    $callback = function ($record) {
      $record['loggerId'] = $record['context']['loggerId'] ?? self::$_loggerId ?? 'SY' . date('YmdHis');
      return $record;
    };
    self::$_logger->pushProcessor($callback);
  }

  /**
   * @param $message
   * @param array $context
   * @return bool
   */
  public static function info($message, array $context = array())
  {
    return self::$_logger->addRecord(Logger::INFO, $message, $context);
  }

  /**
   * @param $message
   * @param array $context
   * @return bool
   */
  public static function debug($message, array $context = array())
  {
    return self::$_logger->addRecord(Logger::DEBUG, $message, $context);
  }

  /**
   * 记录错误消息
   * @param $message
   * @param array $context
   * @return bool
   */
  public static function error($message, array $context = array())
  {
    return self::$_logger->addRecord(Logger::ERROR, $message, $context);
  }

  /**
   * @param $message
   * @param array $context
   * @return bool
   */
  public static function warn($message, array $context = array())
  {
    return self::$_logger->addRecord(Logger::WARNING, $message, $context);
  }

  public function setLoggerId($_loggerId)
  {
    self::$_loggerId = $_loggerId;
  }

}
