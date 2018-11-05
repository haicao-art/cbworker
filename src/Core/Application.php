<?php

# @Author: crababy
# @Date:   2018-03-21T15:54:17+08:00
# @Last modified by:   crababy
# @Last modified time: 2018-09-03T14:17:20+08:00
# @License: http://www.opensource.org/licenses/mit-license.php MIT License
#

namespace Cbworker\Core;

use Cbworker\Core\AbstractInterface\Singleton;
use Cbworker\Core\Config\Config;
use Cbworker\Core\Config\Lang;
use Cbworker\Core\Http\HttpRequest;
use Cbworker\Core\Http\HttpResponse;
use Cbworker\Library\Helper;
use Cbworker\Library\Logger;
use Workerman\Lib\Timer;
use Cbworker\Library\RedisDb;
use Cbworker\Library\StatisticClient;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;

class Application extends Container
{

  use Singleton;

  private $language = 'zh';

  protected $_request = null;

  protected $_response = null;

  protected $_connection = null;

  private function __construct()
  {
    $this->errorHandle();
  }

  private function __clone()
  {
  }

  public function initialize(): Application
  {
    Config::getInstance();

    Lang::getInstance();

    $capsule = new Capsule;

    $capsule->addConnection(Config::getConf('db.mysql'));

    $capsule->setEventDispatcher(new Dispatcher(new Container));

    // 设置全局静态可访问DB
    $capsule->setAsGlobal();

    // 启动Eloquent （如果只使用查询构造器，这个可以注释）
    $capsule->bootEloquent();

    Capsule::listen(function ($query) {
      $sql = vsprintf(str_replace("?", "'%s'", $query->sql), $query->bindings) . " \t[" . $query->time . ' ms] ';
      // 把SQL写入到日志文件中
      Logger::getInstance()->info($sql, 'Sql');
    });
    $this->bind('redis', function () {
      return new RedisDb(Config::getConf('db.redis'));
    });
    $this->bind('logger', function () {
      return Logger::getInstance();
    });
    $this->logger()->debug('initialize Success');
    return $this;
  }

  public function redis()
  {
    return $this['redis'];
  }

  public function logger()
  {
    return $this['logger'];
  }

  public function getConnection()
  {
    return $this->_connection;
  }

  public function connection()
  {
    return $this->_connection;
  }

  public function request()
  {
    return $this->_request;
  }

  public function response() {
    return $this->_response;
  }

  public function init($connection, $data)
  {
    $this->_connection = $connection;
    $this->_request = new HttpRequest($data);
    $this->_response = new HttpResponse();
  }

  /**
   * 定时任务
   */
  public function clearTask()
  {
    Timer::add(86400, array($this, 'clearDisk'), array(Config::getConf('App.Log.LOG_DIR'), Config::getConf('App.Log.ClearTime', '1296000')));
  }

  /**
   * 清除磁盘数据
   * @param  [type]  $file     [description]
   * @param  integer $exp_time [description]
   * @return [type]            [description]
   */
  private function clearDisk($file = null, $exp_time = 86400)
  {
    $now_time = time();
    if (is_file($file)) {
      $mtime = filemtime($file);
      if (!$mtime) {
        return;
      }
      if ($now_time - $mtime > $exp_time) {
        unlink($file);
      }
      return;
    }
    foreach (glob($file . "/*") as $file_name) {
      $this->clearDisk($file_name, $exp_time);
    }
  }

  /**
   * 请求分发
   * @return [type] [description]
   */
  public function methodDispatch($request)
  {
    $controller = Config::getConf('App.NAMESPACE') . 'Controller\\' . $request['class'];

    if (!class_exists($controller) || !method_exists($controller, $request['method'])) {
      throw new \Exception("Controller {$request['class']} or Method {$request['method']} is Not Exists", 1002);
    }

    if (Config::getConf('App.report')) {
      StatisticClient::tick(Config::getConf('App.NAME'), $request['class'], $request['method']);
    }

    try {
      $handler_instance = new $controller($this);
      $_code = $handler_instance->{$request['method']}();
      $this->response()->setCode($_code);
    } catch (\Exception $ex) {
      $this->response()->setCode($ex->getCode());
      $this->response()->setMessage($ex->getMessage());
      $this->logger()->error( '[' $ex->getCode() . ']' . $ex->getMessage(), 'methodDispatch Exception');
    }

    if (Config::getConf('App.report')) {
      StatisticClient::report(Config::getConf('App.NAME'), $request['class'], $request['method'], 0, $this->response()->getCode(), $this->response()->getMessage(), Config::getConf('App.statistic.address'));
    }
  }

  public function Run()
  {
    $_server = $this->request()->server();
    if ($this->request()->uri() === '/favicon.ico') {
      $this->connection()->close('');
      return;
    }
    $request = array();
    /*
    $content_type = $this->request()->contentType();
    if (preg_match("/application\/json/i", $content_type)) {
      $request = json_decode($this->request()->rawData(), TRUE);
    } else if (preg_match("/text\/xml/i", $content_type)) {
      $request = $this->request()->rawData();
    } else {
      $request = $this->request->post();
    }*/
    $_info = explode('/', $this->request()->uri());
    $request['class'] = isset($_info[1]) && !empty($_info[1]) ? ucfirst($_info[1]) : 'Index';
    $request['method'] = isset($_info[2]) && !empty($_info[2]) ? $_info[2] : 'index';

    $this->logger()->info("-----------------{$request['class']}/{$request['method']}-----------------");
    $this->logger()->info($this->request()->userAgent(), 'User_Agent');
    $this->logger()->info($this->request()->post(), 'Params');
    try {
      $this->checkRequestLimit($request['class'], $request['method']);
      $this->methodDispatch($request);
    } catch (\Exception $ex) {
      $this->response()->setCode($ex->getCode());
      $this->response()->setMessage($ex->getMessage());
      $this->logger()->error( '[' $ex->getCode() . ']' . $ex->getMessage(), 'methodDispatch Exception');
    }
    $_raw = $this->response()->getRaw();
    $_headers = $this->response()->header();
    foreach ($_headers as $header) {
      $this->connection()->send($header, $_raw);
    }
    $_responses = $this->response()->build();
    if(empty($_responses)) {
      $this->connection()->send(json_encode(array('code' => $this->response()->getCode(), 'message' => $this->response()->getMessage())), $_raw);
    } else {
      $this->connection()->send($_responses, $_raw);
    }
    if(!$_raw) {
      $this->logger()->info($_responses, 'Response');
    }
    unset($_headers);
    unset($_responses);
    unset($this->_request);
    unset($this->_response);
    unset($this->_connection);
    $this->logger()->info("-----------------END-----------------");
  }

  /**
   * 访问频率限制
   * @return [type] [description]
   */
  private function checkRequestLimit($class, $method)
  {
    $clientIp = Helper::getClientIp();
    $apiLimitKey = "ApiLimit:{$class}:{$method}:{$clientIp}";
    $limitSecond = 10;
    $limitCount = 100000;
    $ret = $this->redis()->RedisCommands('get', $apiLimitKey);
    if (false === $ret) {
      $this->redis()->RedisCommands('setex', $apiLimitKey, $limitSecond, 1);
    } else {
      if ($ret >= $limitCount) {
        $this->redis()->RedisCommands('expire', $apiLimitKey, 10);
        $this->logger()->info("checkRequestLimit: Request Fast", 'checkRequestLimit');
        throw new \Exception("Request faster", -9);
      } else {
        $this->redis()->RedisCommands('incr', $apiLimitKey);
      }
    }
    return true;
  }

  private function errorHandle()
  {
    $func = function () {
      echo 'register shutdown function ' . PHP_EOL;
    };
    register_shutdown_function($func);
  }


}
