<?php
/**
 * Created by PhpStorm.
 * User: xiayiyun
 * Date: 2018/11/4
 * Time: 9:40
 */

namespace Cbworker\Core\Http;

use Workerman\Protocols\Http;
use Cbworker\Core\Config\Config;
use Cbworker\Core\Config\Lang;

class HttpResponse
{

  protected $_code = 200;

  protected $_message = null;

  protected $_data = array();

  protected $_headers = array();

  protected $_raw = false;

  protected $_rawData = '';

  protected $_result = null;

  public function build() {
    if($this->_raw) {
      return $this->_rawData;
    }
    return json_encode(array_merge(array('code' => $this->getCode(), 'message' => $this->getMessage()), $this->_data));
  }

  public function header() {
    $this->defaultHeader();
    return $this->_headers;
  }

  public function getRaw() {
    return $this->_raw;
  }

  public function setRaw($bool = false) {
    $this->_raw = $bool;
  }

  public function getRawData() {
    return $this->_rawData;
  }

  public function setRawData($str) {
    $this->_rawData .= $str;
  }

  public function setCode($code = 0) {
    $this->_code = $code;
  }

  public function getCode() {
    return $this->_code;
  }

  public function setMessage($message) {
    $this->_message = $message;
  }

  public function getMessage() {
    if(empty($this->_message)) {
      $this->_message = Lang::getLang(Config::getConf('App.Language', 'zh') . '.' . $this->_code);
    }
    return $this->_message;
  }

  private function defaultHeader() {
    Http::header("Access-Control-Allow-Origin:*");
    Http::header("Access-Control-Max-Age: 86400");
    Http::header("Access-Control-Allow-Method: POST, GET");
    Http::header("Access-Control-Allow-Headers: Origin, X-CSRF-Token, X-Requested-With, Content-Type, Accept");
  }

  /**
   * [setHeader 设置头部信息]
   * @param array $params [description]
   */
  public function setHeader($params) {
    if(is_array($params)) {
      foreach ($params as $item) {
        $this->_headers[] = $item;
      }
    } else {
      $this->_headers[] = $params;
    }
  }

  /**
   * [setData 设置内容]
   * @param [type] $data [description]
   * @param string $raw  [description]
   */
  public function setData($data) {
    $this->_data = $data;
  }

  public function destroy() {
    $this->_data = null;
    $this->_message = null;
    $this->_rawData = null;
  }


}
