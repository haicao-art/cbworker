<?php
# @Author: crababy
# @Date:   2018-06-22T16:18:17+08:00
# @Last modified by:   crababy
# @Last modified time: 2018-06-22T16:18:23+08:00
# @License: http://www.opensource.org/licenses/mit-license.php MIT License
namespace Cbworker\Library;

use Cbworker\Core\Config\Config;
use Cbworker\Core\AbstractInterface\Singleton;
use Cbworker\Library\Log\CLogger;
use Cbworker\Library\Log\MonoLogger;

class Logger
{
  use Singleton;

  private static $_logger;

  private function __construct()
  {
    $_type = Config::getInstance()->getConf('Log.Type', 'CLogger');
    switch ($_type) {
      case 'CLogger':
        self::$_logger = CLogger::getInstance();
        break;
      case 'MonoLog':
        self::$_logger = MonoLogger::getInstance();
        break;
    }
  }

  public static function info($message, $context = array())
  {
    self::$_logger->logger($message, $context, 'INFO');
  }

  public static function error($message, $context = array())
  {
    self::$_logger->logger($message, $context, 'ERROR');
  }

  public static function debug($message, $context = array())
  {
    self::$_logger->logger($message, $context, 'DEBUG');
  }

  public static function notice($message, $context = array())
  {
    self::$_logger->logger($message, $context, 'NOTICE');
  }
}
