<?php
# @Author: crababy
# @Date:   2018-03-23T17:42:51+08:00
# @Last modified by:   crababy
# @Last modified time: 2018-03-23T17:43:06+08:00
# @License: http://www.opensource.org/licenses/mit-license.php MIT License
#


namespace Cbworker\Core;
use Cbworker\Core\Application;

class Controller {
  /**
   * application
   * @var [type]
   */
  public $application;

  /**
   * 构造函数
   */
  public function __construct(Application $app) {
    $this->application = $app;
  }

  public function app() {
    return $this->application;
  }
}
