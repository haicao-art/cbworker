<?php
# @Author: crababy
# @Date:   2018-03-29T11:19:53+08:00
# @Last modified by:   crababy
# @Last modified time: 2018-03-29T11:20:06+08:00
# @License: http://www.opensource.org/licenses/mit-license.php MIT License

namespace Cbworker\Library;

class LoggerClient {

  public static function record($report_address, $project, $function, $logger_type, $ip, $message) {
    $report_address = $report_address ? $report_address : 'tcp://127.0.0.1:55566';
    $data = array(
      'project' => $project,
      'function' => $function,
      'logger_type' => $logger_type,
      'ip'      => $ip,
      'message' => $message
    );
    $bin_data = LoggerProtocol::encode($data);
    return self::sendData($report_address, $bin_data);
  }

  /**
  * 发送数据给统计系统
  * @param string $address
  * @param string $buffer
  * @return boolean
  */
  public static function sendData($address, $buffer) {
    $socket = @stream_socket_client($address);
    if(!$socket) {
      return false;
    }
    stream_socket_sendto($socket, $buffer) == strlen($buffer);

    fclose($socket);
  }

}


class LoggerProtocol {

  /**
   * 包头长度
   * @var integer
   */
  const PACKAGE_FIXED_LENGTH = 17;
  /**
   * udp 包最大长度
   * @var integer
   */
  const MAX_UDP_PACKGE_SIZE  = 65507;
  /**
   * char类型能保存的最大数值
   * @var integer
   */
  const MAX_CHAR_VALUE = 255;

  /**
   * 编码
   * @param string $module
   * @param string $interface
   * @param float $cost_time
   * @param int $success
   * @param int $code
   * @param string $msg
   * @return string
   */
  public static function encode($data)
  {
    $project = $data['project'];
    $function = $data['function'];
    $logger_type = $data['logger_type'];
    $ip = isset($data['ip']) ? $data['ip'] : '127.0.0.1';
    $message = isset($data['message']) ? $data['message'] : '';
    //防止项目名称过长
    if(strlen($project) > self::MAX_CHAR_VALUE) {
      $project = substr($project, 0, self::MAX_CHAR_VALUE);
    }
    if(strlen($function) > self::MAX_CHAR_VALUE) {
      $function = substr($function, 0, self::MAX_CHAR_VALUE);
    }
    if(strlen($logger_type) > self::MAX_CHAR_VALUE) {
      $logger_type = substr($logger_type, 0, self::MAX_CHAR_VALUE);
    }

    // 防止msg过长
    $project_name_length = strlen($project);
    $function_name_length = strlen($function);
    $logger_type_length = strlen($logger_type);
    $avalible_size = self::MAX_UDP_PACKGE_SIZE - self::PACKAGE_FIXED_LENGTH - $project_name_length - $function_name_length - $logger_type_length;
    if(strlen($message) > $avalible_size) {
      $message = substr($message, 0, $avalible_size);
    }
    return pack('CCCLnNf', $project_name_length, $function_name_length, $logger_type_length, ip2long($ip), strlen($message), time(),  strtok(microtime(), " ")) . $project . $function . $logger_type . $message;
  }

}
