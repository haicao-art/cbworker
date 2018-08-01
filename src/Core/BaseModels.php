<?php
# @Author: crababy
# @Date:   2018-03-23T17:42:51+08:00
# @Last modified by:   crababy
# @Last modified time: 2018-03-23T17:43:06+08:00
# @License: http://www.opensource.org/licenses/mit-license.php MIT License
#


namespace Cbworker\Core;

use Cbworker\Core\Application;

class BaseModels {
  /**
   * redis
   * @var [type]
   */
  public $redis;

  /**
   * mysql
   * @var [type]
   */
  public $mysql;

  /**
   * config
   * @var [type]
   */
  public $config;

  /**
   * 构造函数
   */
  public function __construct(Application $app) {
    $this->mysql = $app['mysql'];
    $this->redis = $app['redis'];
    $this->config = $app['config'];
  }

}
