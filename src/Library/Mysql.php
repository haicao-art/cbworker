<?php
# @Author: crababy
# @Date:   2018-03-23T10:53:18+08:00
# @Last modified by:   crababy
# @Last modified time: 2018-03-24T07:18:40+08:00
# @License: http://www.opensource.org/licenses/mit-license.php MIT License

namespace Cbworker\Library;

use Exception;
use PDO;
use PDOException;
use Cbworker\Library\Helper;

/**
 * 数据库连接类，依赖 PDO_MYSQL 扩展
 */
class Mysql {

  /**
   * pdo 实例
   *
   * @var PDO
   */
  protected $pdo;

  /**
   * PDOStatement 实例
   *
   * @var \PDOStatement
   */
  protected $sQuery;

  /**
   * 实例
   * @var [type]
   */
  protected static $instance = null;

  /**
   * 数据库用户名密码等配置
   *
   * @var array
   */
  protected $settings = array();


	/**
 * 构造函数
 * @param array $options [description]
 */
  public function __construct($options = null)
  {
		$this->settings = array(
			'host'     => isset($options['host']) ? $options['host'] : '127.0.0.1',
			'port'     => isset($options['port']) ? $options['port'] : '3306',
			'user'     => isset($options['username']) ? $options['username'] : 'root',
			'password' => isset($options['password']) ? $options['password'] : '',
			'dbname'   => $options['database_name'],
			'charset'  => isset($options['charset']) ? $options['charset'] : 'utf8mb4'
		);
    $this->connect();
  }

  /**
   * mysql 链接
   * @return [type] [description]
   */
  protected function connect() {
  	try {
      $dsn = 'mysql:dbname=' . $this->settings["dbname"] . ';host=' . $this->settings["host"] . ';port=' . $this->settings['port'];
			$opt = array(
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES ' . (!empty($this->settings['charset']) ? $this->settings['charset'] : 'utf8mb4'),
			);
			$this->pdo = new PDO($dsn, $this->settings["user"], $this->settings["password"], $opt);
      $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      $this->pdo->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);
      $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
		} catch (PDOException $e) {
      Helper::logger('Mysql Connect Error:', $ex->getMessage());
			throw new Exception('数据库连接异常', (int)$e->getCode(), Helper::ERROR);
		}
  }

 /**
  * 关闭连接
  */
  public function closeConnection()
  {
      $this->pdo = null;
  }

  /**
   * 执行SQL
   * @param  [type] $query  [description]
   * @param  [type] $params [description]
   * @return [type]         [description]
   */
  protected function execute($query, $params = null) {
		try {
			$this->sQuery = @$this->pdo->prepare($query);
			if (is_array($params)) {
				foreach ($params as $key => $value) {
					$this->sQuery->bindValue((int)($key + 1), $value);
				}
			}
			$this->sQuery->execute();
		} catch (PDOException $e) {
			if ($e->errorInfo[1] == 2006 || $e->errorInfo[1] == 2013) {
				$this->closeConnection();
        $this->connect();
        try {
					$this->sQuery = $this->pdo->prepare($query);
					if (is_array($params)) {
						foreach ($params as $key => $value) {
							$this->sQuery->bindValue((int)($key + 1), $value);
						}
					}
					$this->sQuery->execute();
        } catch (PDOException $ex) {
          $this->rollBackTrans();
          Helper::logger('Mysql Execute Error:', $ex->getMessage(), Helper::ERROR);
					throw new Exception('SQL Execute Error', (int)$ex->getCode());
        }
			} else {
        $this->rollBackTrans();
        Helper::logger('Mysql Execute Error:', $e->getMessage(), Helper::ERROR);
        throw new Exception('SQL Execute Error', (int)$e->getCode());
      }
    }
  }

  /**
   * 执行sql语句
   * @param  [type]  $query  [description]
   * @param  [type]  $params [description]
   * @param  boolean $single [description]
   * @return [type]          [description]
   */
  public function query($query, $params = array(), $single = false) {
      $query = trim($query);
      Helper::logger('Mysql Execute Query:', [$query, $params]);
      $this->execute($query, $params);
      $rawStatement = explode(" ", $query);
      $statement = strtolower(trim($rawStatement[0]));
      $result = false;
      if ($statement === 'select' || $statement === 'show') {
      	$this->sQuery->setFetchMode(PDO::FETCH_ASSOC);
      	$result = true === $single ? $this->sQuery->fetch() : $this->sQuery->fetchAll();
      } elseif ($statement === 'update' || $statement === 'delete') {
        if ($this->sQuery->rowCount() > 0) {
          $result = $this->sQuery->rowCount();
        }
      } elseif ($statement === 'insert') {
        if ($this->sQuery->rowCount() > 0) {
          $result = $this->pdo->lastInsertId();
        }
      }
      Helper::logger('Mysql Execute Result:', $result);
      return $result;
  }

  /**
   * 生成mysql的prepare
   * @param [type] $fields    [description]
   * @param string $separator [description]
   */
  public function build($fields, $separator = ',') {
  	$ret = array(
  		'sqlPrepare' => '',
  		'bindParams' => array()
  	);
  	if (!is_array($fields) || count($fields) < 1) {
  		return $ret;
  	}
  	$ret['sqlPrepare'] = array();
  	foreach ($fields as $k => $v) {
  		if (is_array($v)) {
        list($separ, $value) = $v;
    		$ret['sqlPrepare'][] = "{$k} {$separ} ? ";
    		$ret['bindParams'][] = $value;
  		} else {
    		$ret['sqlPrepare'][] = "{$k} = ? ";
    		$ret['bindParams'][] = $v;
      }
  	}
  	$ret['sqlPrepare'] = implode($separator, $ret['sqlPrepare']);
  	return $ret;
  }

  /**
   * 开始事务
   */
  public function beginTrans()
  {
    Helper::logger("Mysql BeginTrans:", 'BeginTrans:Start');
    try {
      return $this->pdo->beginTransaction();
    } catch (PDOException $e) {
      // 服务端断开时重连一次
      if ($e->errorInfo[1] == 2006 || $e->errorInfo[1] == 2013) {
        $this->closeConnection();
        $this->connect();
        return $this->pdo->beginTransaction();
      } else {
        Helper::logger("Mysql BeginTrans Error:", $e->getMessage(), Helper::ERROR);
		    throw new Exception('Database Error', (int)$e->getCode());
      }
    }
  }
  /**
   * 提交事务
   */
  public function commitTrans()
  {
    Helper::logger("Mysql CommitTrans", 'CommitTrans Commit');
    return $this->pdo->commit();
  }
  /**
   * 事务回滚
   */
  public function rollBackTrans()
  {
    if ($this->pdo->inTransaction()) {
      Helper::logger("Mysql RollBackTrans", 'RollBackTrans End');
      return $this->pdo->rollBack();
    }
    return true;
  }
}
