<?php

# @Author: crababy
# @Date:   2018-03-21T15:54:17+08:00
# @Last modified by:   crababy
# @Last modified time: 2018-03-21T15:54:24+08:00
# @License: http://www.opensource.org/licenses/mit-license.php MIT License
#

namespace Cbworker\Core;

use Cbworker\Core\Container;
use Cbworker\Core\Event\Event;
use \Workerman\Protocols\Http;
use Cbworker\Library\Helper;
use Cbworker\Library\Mysql;
use Cbworker\Library\RedisDb;
use Cbworker\Library\Queue;

class Application extends Container  {

  private $language = 'zh';

  private static $_instance = null;

  public static function getInstance() {
    if(empty(self::$_instance)) {
      self::$_instance = new self();
    }
    return self::$_instance;
  }

  private function __construct() {
    $this->setShared('config', function() {
      require_once ROOT_PATH . '/Config/Config.php';
      return $config;
    });

    $this->setShared('mysql', function() {
      return new Mysql($this['config']['mysql']);
    });

    $this->setShared('redis', function() {
      return new RedisDb($this['config']['redis']);
    });

    $this->setShared('queue', function() {
      return new Queue($this['redis']);
    });

    $this->setShared('lang', function() {
      require_once ROOT_PATH . '/Config/Lang.php';
      return $lang;
    });
    //监听事件
    Event::listen('init', function() {});
  }

  private function __clone() {}

  /**
   * 启动
   * @return [type] [description]
   */
  public function run(&$rsp) {
    //触发事件
    Event::tigger('init');
    $content_type = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
    if (!preg_match("/application\/x-www-form-urlencoded/i", $content_type)) {
      $req = json_decode($GLOBALS['HTTP_RAW_POST_DATA'], TRUE);
    } else {
      $req = array_merge($_GET, $_POST);
    }
    try {
      $this->getLanguage();
      $this->check();
      $this->methodDispatch($req, $rsp);
    } catch (\Exception $ex) {
      $rsp['code'] = $ex->getCode();
      $rsp['desc'] = $ex->getMessage();
      Helper::logger("Run:", $ex->getMessage(), Helper::ERROR);
    }
    $this->formatMessage($rsp);
  }

  /**
   * 任务启动器
   * @param  array  $task_data [description]
   * @return [type]            [description]
   */
  public function taskRun($req, &$rsp) {
    try {
      $this->getLanguage();
      $task_data = json_decode($req, true);
      list($class, $method) = explode('/', $task_data['uri']);
      $task_controller = $this['config']['namespace'].ucfirst($class);
      if(!class_exists($task_controller) || !method_exists($task_controller, $method)) {
        throw new \Exception("Task Controller {$task_controller} or Method {$method} is Not Exists", 1002);
      }
      $handler_instance = new $task_controller($this);
      $rsp['code'] = $handler_instance->$method($task_data['params'], $rsp);
      $rsp['desc'] = isset($this['lang'][$this->language][$rsp['code']]) ? $this['lang'][$this->language][$rsp['code']] : "系统异常[{$rsp['code']}]";
    } catch (\Exception $ex) {
      $rsp['code'] = $ex->getCode();
      $rsp['desc'] = $ex->getMessage();
      Helper::logger("Task:", $ex->getMessage(), Helper::ERROR);
    }
  }

  /**
   * 请求分发
   * @return [type] [description]
   */
  private function methodDispatch($req, &$rsp) {
    $url_info = parse_url($_SERVER['REQUEST_URI']);
    $_info = explode('/', $url_info['path']);;
    $class = isset($_info[1]) && !empty($_info[1]) ? $_info[1] : 'index';
    $method = isset($_info[2]) && !empty($_info[2]) ? $_info[2] : 'index';

    $controller = $this['config']['namespace'].ucfirst($class);

    if(!class_exists($controller) || !method_exists($controller, $method)) {
      throw new \Exception("Controller {$class} or Method {$method} is Not Exists", 1002);
    }
    Helper::logger('Start:', '----------------------------------');
    Helper::logger("Request:", $url_info['path']);
    Helper::logger("Params:", $req);

    //请求频率校验
    $this->checkRequestLimit($class, $method);

    $handler_instance = new $controller($this);
    $rsp['code'] = $handler_instance->$method($req, $rsp);
    Helper::logger("Result:", $rsp);
    Helper::logger('End:', '----------------------------------');
  }

  /**
   * 返回JSON数据
   * @param array $data [description]
   */
  private function formatMessage(&$response) {
    Http::header("Access-Control-Allow-Origin:*");
    Http::header("Access-Control-Max-Age: 86400");
    Http::header("Access-Control-Allow-Method: POST, GET");
    Http::header("Access-Control-Allow-Headers: Origin, X-CSRF-Token, X-Requested-With, Content-Type, Accept");
    Http::header("Content-type: application/json;charset=utf-8");
    $response['code'] = isset($response['code']) ? $response['code'] : 0;
    $response['desc'] = isset($this['lang'][$this->language][$response['code']]) ? $this['lang'][$this->language][$response['code']] : "系统异常[{$response['code']}]";
    //Helper::logger('Response Result:', $response);
    //Http::end(json_encode($response, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK));
    //return json_encode($response, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
  }


  /**
   * 访问频率限制
   * @return [type] [description]
   */
  private function checkRequestLimit($class, $method) {
    $clientIp = Helper::getClientIp();
    $apiLimitKey = "ApiLimit:{$class}:{$method}:{$clientIp}";
    $limitSecond = isset($this['config']['apiLimit'][$class][$method]['limitSecond']) ? $this['config']['apiLimit'][$class][$method]['limitSecond'] : 10;
    $limitCount = isset($this['config']['apiLimit'][$class][$method]['limitCount']) ? $this['config']['apiLimit'][$class][$method]['limitCount'] : 100000;
    $ret = $this['redis']->RedisCommands('get', $apiLimitKey);
    if (false === $ret) {
      $this['redis']->RedisCommands('setex', $apiLimitKey, $limitSecond, 1);
    } else {
      if($ret >= $limitCount) {
        $this['redis']->RedisCommands('expire', $apiLimitKey, 10);
        Helper::logger('checkRequestLimit:', "{$ret} Request Fast");
        throw new \Exception("Request faster", 1005);
      } else {
        $this['redis']->RedisCommands('incr', $apiLimitKey);
      }
    }
    return true;
  }

  /**
   * 校验请求方式
   * @return [type] [description]
   */
  private function check() {
    /*
    if(!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
      throw new \Exception("Error Request Method", -1);
    }
    */
  }

  /**
   * 设置语言类型
   * @return [type] [description]
   */
  private function getLanguage($default = 'zh') {
    if(isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
      $language = strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE']);
      if(in_array($language, array('zh', 'en'))) {
        $this->language = $language;
      }
    } else {
      $this->language = $default;
    }
  }


}
