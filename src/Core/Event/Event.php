<?php
# @Author: crababy
# @Date:   2018-06-21T13:34:44+08:00
# @Last modified by:   crababy
# @Last modified time: 2018-06-21T13:34:52+08:00
# @License: http://www.opensource.org/licenses/mit-license.php MIT License

namespace Cbworker\Core\Event;

class Event {

  protected static $listens = [];

  /**
   * [listen 注册监听事件]
   * @param  [type]  $event    [事件名称]
   * @param  [type]  $callback [事件回调]
   * @param  boolean $once     [是否是一次性事件]
   * @return [type]            [bool]
   */
  public static function listen($event, $callback, $once = false) {
    if(!is_callable($callback)) return false;
    self::$listens[$event][] = array('callback' => $callback, 'once' => $once);
    return true;
  }

  /**
   * [once 一次性事件]
   * @param  [type] $event    [事件名称]
   * @param  [type] $callback [事件回调]
   * @return [type]           [bool]
   */
  public static function once($event, $callback) {
    return self::listen($event, $callback, true);
  }

  /**
   * [remove 移除事件]
   * @param  [type] $event [description]
   * @param  [type] $index [description]
   * @return [type]        [description]
   */
  public static function remove($event, $index = null) {
    if(is_null($index)) {
      unset(self::$listens[$event]);
    } else {
      unset(self::$listens[$event][$index]);
    }
  }

  public static function tigger() {
    /*没有传递参数(事件) 退出*/
    if(!func_num_args()) return false;

    $args = func_get_args();
    //获取事件名称
    $event = array_shift($args);
    //检测事件是否注册
    if(!isset(self::$listens[$event])) return false;
    foreach (self::$listens[$event] as $index => $listen) {
      $callback = $listen['callback'];
      $listen['once'] && self::remove($event, $index);
      call_user_func_array($callback, $args);
    }
  }

}
