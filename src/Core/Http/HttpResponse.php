<?php
/**
 * Created by PhpStorm.
 * User: xiayiyun
 * Date: 2018/11/4
 * Time: 9:40
 */

namespace Cbworker\Core\Http;

use Workerman\Protocols\Http;

class HttpResponse
{
  
  protected $_result = null;
  
  public function build() {
    $this->sendHeaders();
    return $this->_result;
  }
  
  private function sendHeaders() {
    Http::header("Access-Control-Allow-Origin:*");
    Http::header("Access-Control-Max-Age: 86400");
    Http::header("Access-Control-Allow-Method: POST, GET");
    Http::header("Access-Control-Allow-Headers: Origin, X-CSRF-Token, X-Requested-With, Content-Type, Accept");
    Http::header("Content-type: application/json;charset=utf-8");
  }
  
  public function setData($data) {
    $this->_result = json_encode($data);
  }
  
  
}