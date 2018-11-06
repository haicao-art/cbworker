<?php

# @Author: crababy
# @Date:   2018-03-23T17:42:16+08:00
# @Last modified by:   crababy
# @Last modified time: 2018-03-23T17:49:01+08:00
# @License: http://www.opensource.org/licenses/mit-license.php MIT License

namespace Application\Iot\Controller;

use Cbworker\Core\Controller;
use Cbworker\Library\Helper;

/**
 * index
 */
class Base extends Controller {



  /**
   * 验证是否登录
   * @param [type] $token [description]
   */
  public function ValidateLogin($token) {
    $userId = Helper::decodeUserToken($token);
    $fields = ['id', 'token', 'username', 'nickname', 'avatar', 'email', 'mobile', 'roles', 'state', 'addtime'];
    //$this->redis->RedisCommands('del', 'user:'.$userId);
    $user = $this->redis->RedisCommands('hGetAll', 'user:'.$userId);
    if (count($user) < count($fields)) {
      $fieldStmt = implode(',', $fields);
      $user = $this->mysql->query("select {$fieldStmt} from users where id = ? and token = ? ", array($userId, $token), true);
      if (false === $user) {
        return false;
      }
      $this->redis->RedisCommands('hMset', "user:{$user['id']}", $user);
      $this->redis->RedisCommands('setTimeout', "user:{$user['id']}", 3600);
    }
    if($token != $user['token']) {
      return false;
    }
    return $user;
  }

  public function clearUserCache($userId) {
    $this->redis->RedisCommands('del', 'user:'.$userId);
    return true;
  }
}
