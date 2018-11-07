<?php
/**
 * Created by PhpStorm.
 * User: xiayiyun
 * Date: 2018/11/4
 * Time: 10:27
 */

namespace Cbworker\Core\Http;


use Cbworker\Core\AbstractInterface\Request;

class HttpRequest implements Request
{
  protected $_get;

  protected $_post;

  protected $_server;

  protected $_cookie;

  protected $_files;

  protected $_rawData;

  public function __construct($params = array())
  {
    $this->_get     = isset($params['get']) ? $params['get'] : array();
    $this->_post    = isset($params['post']) ? $params['post'] : array();
    $this->_server  = isset($params['server']) ? $params['server'] : array();
    $this->_files   = isset($params['files']) ? $params['files'] : array();
    $this->_cookie  = isset($params['cookie']) ? $params['cookie'] : array();
    $this->_rawData = $GLOBALS['HTTP_RAW_POST_DATA'];
  }

  public function get($key = '', $default = '') {
    if(!empty($key)) {
      return isset($this->_get[$key]) ? $this->_get[$key] : $default;
    }
    return $this->_get;
  }

  public function post($key = '', $default = '') {
    if(!empty($key)) {
      return isset($this->_post[$key]) ? $this->_post[$key] : $default;
    }
    return $this->_post;
  }

  public function request($key = '', $default = '') {
    if(!empty($key)) {
      return isset($this->_post[$key]) ? $this->_post[$key] : $default;
    }
    return $this->_post;
  }

  public function cookie($key = '', $default = '') {
    if(!empty($key)) {
      return isset($this->_cookie[$key]) ? $this->_cookie[$key] : $default;
    }
    return $this->_cookie;
  }

  public function server($key = '', $default = '') {
    if(!empty($key)) {
      return isset($this->_server[$key]) ? $this->_server[$key] : $default;
    }
    return $this->_server;
  }

  public function rawData() {
    return $this->_rawData;
  }

  public function method() {
    return isset($this->_server['REQUEST_METHOD']) ? $this->_server['REQUEST_METHOD'] : 'POST';
  }

  public function origin() {
    return isset($this->_server['HTTP_ORIGIN']) ? $this->_server['HTTP_ORIGIN'] : 'unknown';
  }

  public function contentType() {
    return isset($this->_server['HTTP_CONTENT_TYPE']) ? $this->_server['HTTP_CONTENT_TYPE'] : 'application/x-www-form-urlencoded';
  }

  public function userAgent() {
    return isset($this->_server['HTTP_USER_AGENT']) ? $this->_server['HTTP_USER_AGENT'] : 'unknown';
  }

  public function uri() {
    return isset($this->_server['REQUEST_URI']) ? $this->_server['REQUEST_URI'] : 'unknown';
  }

  public function path() {
    return parse_url($this->_server['REQUEST_URI'])['path'];
  }

  public function destroy() {
    $this->_get = null;
    $this->_post = null;
    $this->_cookie = null;
    $this->_server = null;
    $this->_files = null;
    $this->_rawData = null;
  }


}
