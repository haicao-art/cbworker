<?php

# @Author: crababy
# @Date:   2018-03-21T17:48:57+08:00
# @Last modified by:   crababy
# @Last modified time: 2018-03-21T17:50:25+08:00
# @License: http://www.opensource.org/licenses/mit-license.php MIT License


namespace Cbworker\Core;


class OwnContainer implements \ArrayAccess {

  /**
   * 服务列表
   * @var [type]
   */
  private $_bindings = array();

  /**
   * 已经实例化的服务
   * @var [type]
   */
  private $_instances = array();

  /**
   * 获取服务
   * @param  [type] $name   [description]
   * @param  array  $params [description]
   * @return [type]         [description]
   */
  public function get($name, $params = array()) {

    //如果已经实例化 直接返回
    if(isset($this->_instances[$name])) {
      return $this->_instances[$name];
    }

    //服务没有注册，返回NULL
    if(!isset($this->_bindings[$name])) {
      return null;
    }

    $concrete = $this->_bindings[$name]['class'];

    $obj = null;

    if($concrete instanceof \Closure) {
      $obj = call_user_func_array($concrete, $params);
    } else if (is_string($concrete)) {
      if(empty($params)) {
        $obj = new $concrete;
      } else {
        //带参数的实例化
        $class = new \ReflectionClass($concrete);
        $obj = $class->newInstanceArgs($params);
      }
    }
    //如果是共享服务,写入_instances
    if($this->_bindings[$name]['shared'] === true && $obj) {
      $this->_instances[$name] = $obj;
    }
    return $obj;
  }

  //检测是否已绑定
  public function has($name) {
    return isset($this->_bindings[$name]) or isset($this->_instances[$name]);
  }

  //卸载服务
  public function remove($name) {
    unset($this->_bindings[$name], $this->_instances[$name]);
  }

  //注册服务
  public function set($name, $class) {
    $this->_registerService($name, $class);
  }

  //注册共享服务
  public function setShared($name, $class) {
    $this->_registerService($name, $class, true);
  }

  private function _registerService($name, $class, $shared = false) {
    $this->remove($name);
    if(!($class instanceof \Closure) && is_object($class)) {
      $this->_instances[$name] = $class;
    } else {
      $this->_bindings[$name] = array('class' => $class, 'shared' => $shared);
    }
  }

  //ArrayAccess 接口 检测服务是否存在
  public function offsetExists($offset) {
    return $this->has($offset);
  }

  //ArrayAccess接口 $di[$name方式获取服务
  public function offsetGet($offset) {
    return $this->get($offset);
  }

  //以￥di[$name]=value方式注册
  public function offsetSet($offset, $value) {
    return $this->set($offset, $value);
  }

  //unset($di[$name])卸载服务
  public function offsetUnset($offset) {
    return $this->remove($offset);
  }

}
