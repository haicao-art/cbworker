<?php
# @Author: crababy
# @Date:   2018-06-21T11:13:24+08:00
# @Last modified by:   crababy
# @Last modified time: 2018-06-21T11:13:31+08:00
# @License: http://www.opensource.org/licenses/mit-license.php MIT License

namespace Cbworker\Library;

use Exception;
use Cbworker\Library\Helper;

/**
 * 队列
 */
class Queue {

  protected $redis;

  protected $default;

  public function __construct($redis, $default = 'default') {
    $this->redis = $redis;
    $this->default = $default;
  }

  public function push($job, $data = '', $queue = null) {
    $this->redis->RedisCommands('rpush', $this->getQueue($queue), json_encode($this->createPlainPayload($job, $data)));
  }

  protected function getQueue($queue) {
    return 'queues:'.($queue ?: $this->default);
  }

  protected function createPlainPayload($job, $data) {
    $payload = [
      'job' => $job,
      'data' => $data,
      'attempts'  => 1
    ];
    return $payload;
  }

  protected function setMeta($payload, $key, $value) {
    $payload = json_decode($payload, true);
    $payload[$key] = $value;
    return json_encode($payload);
  }
}
