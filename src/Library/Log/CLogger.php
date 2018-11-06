<?php
# @Author: crababy
# @Date:   2018-06-22T16:18:17+08:00
# @Last modified by:   crababy
# @Last modified time: 2018-06-22T16:18:23+08:00
# @License: http://www.opensource.org/licenses/mit-license.php MIT License
namespace Cbworker\Library\Log;

use Cbworker\Core\Config\Config;
use Cbworker\Core\AbstractInterface\Singleton;

class CLogger
{
  use Singleton;
  const INFO = 'INFO';
  const DEBUG = 'DEBUG';
  const ERROR = 'ERROR';
  protected static $log_dir;

  private function __construct()
  {
    self::$log_dir = Config::getInstance()->getConf('Log.LOG_DIR');
    if (!file_exists(self::$log_dir)) {
      mkdir(self::$log_dir, 0777, true);
    }
  }

  public static function logger($tag, $message, $level = 'INFO')
  {
    if(!in_array($level, explode(',', Config::getConf('Log.LogLevel', 'ERROR')))) {
      return;
    }
    $tag = !empty($tag) ? $tag . ':' : '';
    $startLine = $level . "|" . getmypid() . "|" . date("m-d H:i:s ") . strtok(microtime(), " ") . "|" . $tag;
    if (is_array($message)) {
      $startLine .= json_encode($message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    } else {
      $message = false === $message ? "false" : $message;
      $startLine .= $message . PHP_EOL;
    }
    error_log($startLine, 3, self::$log_dir . DIRECTORY_SEPARATOR . date('Y-m-d') . '.log');
  }
}
