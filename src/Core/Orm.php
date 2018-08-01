<?php
# @Author: crababy
# @Date:   2018-06-13T10:01:26+08:00
# @Last modified by:   crababy
# @Last modified time: 2018-06-13T10:01:43+08:00
# @License: http://www.opensource.org/licenses/mit-license.php MIT License

namespace Cbworker\Core;

use ArrayAccess;
use Cbworker\Core\BaseModels;

class Orm extends BaseModels {


  protected $table;

  /**
   * fields
   * @var [type]
   */
  protected $fields;

  /**
   * sql
   * @var [type]
   */
  protected $sql;

  /**
   * where
   * @var [type]
   */
  protected $where = false;

  /**
   * params
   * @var [type]
   */
  protected $params = [];

  /**
   * 全局参数
   * @var [type]
   */
  private $alias = [];

  /**
   * 设置字段
   * @param  [type] $field [description]
   * @return [type]        [description]
   */
  public function filed($field) {
    if(is_array($field)) {
      $field = implode(',', $field);
    }
    $this->fields = $field;
    return $this;
  }

  /**
   * where
   * @param  [type] $where [description]
   * @return [type]        [description]
   */
  public function where($where) {
    $this->where[] = $where;
    return $this;
  }

  private function parseSql() {
    $this->sql = 'select * from ' . $this->table;
    if(!empty($this->fields)) {
      $this->sql = str_replace('*', $this->fields, $this->sql);
    }
    $this->buildParams();
  }

  private function buildParams() {
    if($this->where) {
      $this->sql .= ' where ';
      foreach ($this->where as $key => $item) {
        $stmt = $this->mysql->build($item, ' and ');
        $this->sql .= $stmt['sqlPrepare'];
        $this->params = array_merge($this->params, $stmt['bindParams']);
      }
    }
  }

  public function select() {
    $this->parseSql();
    echo $this->sql;
    print_r($this->params);
    $result = $this->mysql->query($this->sql, $this->params);
    return $result;
  }


}
