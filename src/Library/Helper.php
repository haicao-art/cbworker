<?php

/**
 * 日志记录
 * @author workerman.net
 */

namespace Cbworker\Library;

use \Workerman\Connection\AsyncTcpConnection;
use Cbworker\Library\LoggerClient;
use Exception;

class Helper
{

	const DEBUG   = 'Debug';

	const INFO    = 'Info';

	const WARNING = 'Waring';

	const ERROR   = 'Error';

	const FAIL    = 'Fail';

	public static $options = array();

	/**
	 * 日志记录
	 * @param  [type] $message [description]
	 * @param  [type] $level   [description]
	 * @param  string $path    [description]
	 * @return [type]          [description]
	 */
	public static function logger($tag = 'TAG:', $message = '', $level = Helper::INFO) {
		if(false === self::$options['record'] && !in_array($level, array(self::ERROR, self::FAIL))) {
			return;
		}
		if(is_array($message) || is_object($message)) {
			$message = json_encode($message, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
		}
		if(isset(self::$options['recordType']) && self::$options['recordType'] == 'tcp') {
			LoggerClient::record(self::$options['address'], 'CrababyFramework', '', $level, self::getClientIp(), $tag . $message);
		} else {
			$logPath = isset(self::$options['logPath']) ? self::$options['logPath'] : ROOT_PATH . '/Runtime/Logs/';
			$message = false === $message ? "false" : $message;
			$startLine = self::getClientIp() .  "|" . $level . "|" . getmypid() . "|" . date("m-d H:i:s ") . strtok(microtime(), " ") . "|";;
			$logLine = $startLine . $tag . $message . PHP_EOL;
			error_log($logLine, 3,  $logPath . date('Y-m-d') . '.log');
		}
		return true;
	}

	/**
	 * 命令行 输出日志
	 * @param  [type] $message [description]
	 * @return [type]          [description]
	 */
	public function dlogger($message) {
		$startLine = self::getClientIp() . " pid[" . getmypid() . "][" . date("m-d H:i:s ") . strtok(microtime(), " ") . "]";;
		$logLine = $startLine . "\t" . json_encode($message, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK) . PHP_EOL;
		echo $logLine;
	}

	/**
 	* 生成订单号
 	*/
	public static function generateRand($base = 'M') {
  	return $base . date("ymd") . "-" . date("His") . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
	}

	/**
	 * 生成随机字符串
	 */
	function generateRandomStr($length = 6) {
    $str = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';//62个字符
    $strlen = 62;
    while ($length > $strlen) {
        $str .= $str;
        $strlen += 62;
    }
    $str = str_shuffle($str);
    return substr($str, 0, $length);
	}


  /**
   * 请求参数正则校验
   * @param [type] $rules    [description]
   * @param [type] $request  [description]
   * @param [type] $response [description]
   */
  public static function ValidateParams($rules, &$request, &$response) {
    /*清洗掉rules中未定义的参数*/
  	//$request = array_intersect_key($request, $rules);
    foreach ($rules as $key => $rule) {
			/*深层递归匹配*/
			if (is_array($rule)) {
				if (isset($request[$key]) && !is_array($request[$key])) {
					$response['requestError'][$key] = $rule;
					$response['request'][$key] = $request[$key];
					throw new Exception('', 1004);
				}
				/*若是对索引数组的匹配*/
				if (1 == count($rule) && 0 === @array_keys($rule)[0]) {
					$values = array_values($request[$key]);
					foreach ($values as $value) {
						if('^true|false$/' == substr($rule[0], -13)){
							/*强制转换bool类型 http_build_query可能转换true|false为1|0*/
							$request[$key] = (bool)$request[$key];
						}else{
							if (!preg_match($rule[0], $value)) {
								$response['requestError'][$key] = $rule;
			          $response['request'][$key] = $request[$key];
								throw new Exception('', 1004);
							}
						}
					}
					continue;
				}
				/*若是关联数组*/
				if (true === self::ValidateParams($rule, $request[$key], $response['requestError'][$key])) {
					unset($response['requestError'][$key]);
				}
				continue;
			}
			/*若未找到参数检查是否为可选项*/
			if (!isset($request[$key])){
				if(substr($rule, 0, 4) != '/^$|') {
          $response['requestError'][$key] = $rule;
          $response['request'][$key] = null;
					throw new Exception('', 1004);
				} else {
					continue;
				}
			}
      if('^true|false$/' == substr($rule, -13)) {
        $request[$key] = (bool) $request[$key];
      } else {
        if(!preg_match($rule, $request[$key])) {
          $response['requestError'][$key] = $rule;
          $response['request'][$key] = $request[$key];
					throw new Exception('', 1004);
        }
      }
			/*不允许有空值参数, 空数组可以*/
			if ('' == $request[$key]) {
				unset($request[$key]);
			}
    }
    return true;
  }

  /**
   * 获取客户端IP地址
   * @return [type] [description]
   */
  public static function getClientIp() {
		if (!empty($_SERVER["HTTP_CLIENT_IP"])) {
			$cip = $_SERVER["HTTP_CLIENT_IP"];
		} else if (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
			$cip = $_SERVER["HTTP_X_FORWARDED_FOR"];
		} else if (!empty($_SERVER["REMOTE_ADDR"])) {
			$cip = $_SERVER["REMOTE_ADDR"];
		} else {
			$cip = '';
		}
		preg_match("/[\d\.]{7,15}/", $cip, $cips);
		$cip = isset($cips[0]) ? $cips[0] : '127.0.0.1';
		unset($cips);
		return $cip;
  }

  /**
   * 记录使用时长
   * @param  string $tag [description]
   * @return [type]      [description]
   */
  public static function timer() {
  	list($usec, $sec) = explode(" ", microtime());
  	return (float) $usec + (float) $sec;
  }

  /**
   * 内存使用
   */
  public function memory_usage() {
  	$memory = (!function_exists('memory_get_usage')) ? 0 : (memory_get_usage()/1024/1024) . 'MB';
  	return $memory;
  }

	public static function formatBytes($bytes, $precision = 2) {
    $units = array("b", "kb", "mb", "gb", "tb");
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, $precision) . " " . $units[$pow];
	}

	/**
	 * 请求API接口
	 * @param  [type] $url  [description]
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	public static function httpRequest($url, $data, $header = null, $ssl = false, $timeout = 60) {
		$ch = curl_init();
		/*相对路径转换*/
		if ('http' != substr($url, 0, 4)) {
			$url = ('' == $_SERVER['HTTPS'] ? 'http://' : 'https://') . dirname($_SERVER['HTTP_HOST'] . ":" . $_SERVER['SERVER_PORT'] . $_SERVER['REQUEST_URI']) . "/" . $url;
		}
		curl_setopt($ch, CURLOPT_URL, $url);
		/*默认30s超时*/
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		/*在curl_exec中返回结果而不是在buf中输出*/
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		/*输出不带header*/
		curl_setopt($ch, CURLOPT_HEADER, false);
		/*存在header则携带*/
		if (is_array($header)) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		}
		if (!empty($data)) {
			if (is_array($data)) {
				/*TODO 要重写http_build_query 将bool转成了1&0 */
				$data = http_build_query($data, '', '&');
			}
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		}
		/*SSL证书 cert 与 key 分别属于两个.pem文件*/
		if (is_array($ssl) && isset($ssl['cert']) && isset($ssl['key'])) {
			curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
			curl_setopt($ch, CURLOPT_SSLCERT, $ssl['cert']);
			curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
			curl_setopt($ch, CURLOPT_SSLKEY, $ssl['key']);
		} else {
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		}
		/*执行*/
		$result = curl_exec($ch);
		if (false === $result) {
			$errno = curl_errno($ch);
			$error = curl_error($ch);
		}
		curl_close($ch);
		return $result;
	}


  /**
   * 返回32位带userId Token
   * @param  [type] $userId [description]
   * @return [type]         [description]
   */
  public static function encodeUserToken($userId) {
    $token = self::to62($userId) . '|' . md5(self::generateRand('Token'));
    return substr($token, 0, 32);
  }

	/**
	 * 从token中获取userId
	 * @param  [type] $token [description]
	 * @return [type]        [description]
	 */
	public static function decodeUserToken($token) {
		$userId = strtok($token, '|');
		return $userId == $token ? 0 : self::from62($userId);
	}

	/**
	 * 十进制数转62进制数
	 * @param  [type] $num [description]
	 * @return [type]      [description]
	 */
	private static function to62($num, $to = 62) {
		$dict = 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$ret = '';
		do {
			$ret = $dict[bcmod($num, $to)] . $ret;
			$num = bcdiv($num, $to);
		} while ($num > 0);
		return $ret;
	}

	/**
	 * 62进制转10进制数
	 * @param  [type] $str [description]
	 * @return [type]      [description]
	 */
	private static function from62($str, $from = 62) {
		$dict = 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$str = strval($str);
		$len = strlen($str);
		$dec = 0;
		for ($i = 0; $i < $len; $i++) {
			$pos = strpos($dict, $str[$i]);
			$dec = bcadd(bcmul(bcpow($from, $len - $i - 1), $pos), $dec);
		}
		return $dec;
	}

	/**
	 * 投递任务
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	public static function task($data, $callback) {
		$task_connection = new AsyncTcpConnection('Text://127.0.0.1:55757');
		$task_connection->send(json_encode($data));
		// 异步获得结果
		$task_connection->onMessage = function($task_connection, $task_result) use ($callback) {
			$callback($task_result);
			// 获得结果后记得关闭链接
			$task_connection->close();
		};

		// 执行异步链接
		$task_connection->connect();
		//
		$task_connection->onError = function($connection, $err_code, $err_msg) {
			echo "$err_code, $err_msg\n\n";
		};
	}
}


//spl_autoload_register('\Applications\Libs\Helper::loadConfig');
