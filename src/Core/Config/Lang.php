<?php
# @Author: crababy
# @Date:   2018-06-22T10:12:24+08:00
# @Last modified by:   crababy
# @Last modified time: 2018-06-22T10:12:29+08:00
# @License: http://www.opensource.org/licenses/mit-license.php MIT License
namespace Cbworker\Core\Config;

use Cbworker\Core\AbstractInterface\Singleton;

class Lang
{
  use Singleton;
  protected static $cached;
  protected static $paths;

  private function __construct($base_path = '.')
  {
    static::$paths = $base_path . DIRECTORY_SEPARATOR . 'Lang' . DIRECTORY_SEPARATOR;
  }

  /**
   * 加载config文件
   * @param  [type] $file [description]
   * @return [type]       [description]
   */
  public static function load($file)
  {
    $file_path = static::$paths . $file . '.php';
    if (isset(static::$cached[$file])) {
      return static::$cached[$file];
    } else if (file_exists($file_path)) {
      return static::$cached[$file] = require_once $file_path;
    }
    return array();
  }

  public static function getAllLang()
  {
    return static::$cached;
  }

  /**
   * [getConf 获取文件配置]
   * @param  [type] $keys    [description]
   * @param  string $default [description]
   * @return [type]          [description]
   */
  public static function getLang($keys, $default = '')
  {
    list($file, $key) = explode('.', $keys, 2);
    $data = static::load(ucfirst($file));
    if (!empty($key)) {
      $result = static::_get($key, $data);
      return false === $result ? $default : $result;
    }
    return false;
  }

  public static function setLang($keys, $value)
  {
    list($file, $key) = explode('.', $keys, 2);
    static::_set(static::$cached[$file], $key, $value);
  }

  private static function _get($path, $data)
  {
    $paths = explode(".", $path);
    while ($key = array_shift($paths)) {
      if (isset($data[$key])) {
        $data = $data[$key];
      } else {
        return false;
      }
    }
    return $data;
  }

  private static function _set(&$array, $path, $value): void
  {
    $path = explode(".", $path);
    while ($key = array_shift($path)) {
      if (!isset($array[$key]) || !is_array($array[$key])) {
        $array[$key] = [];
      }
      $array = &$array[$key];
    }
    $array = $value;
  }
}
