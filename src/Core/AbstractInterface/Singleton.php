<?php
# @Author: crababy
# @Date:   2018-06-22T10:14:15+08:00
# @Last modified by:   crababy
# @Last modified time: 2018-06-22T10:14:18+08:00
# @License: http://www.opensource.org/licenses/mit-license.php MIT License
namespace Cbworker\Core\AbstractInterface;
/**
 * trait 单列模式
 */
trait Singleton {
    private static $_instance;
    static function getInstance(...$args) {
        if(!isset(self::$_instance)) {
            self::$_instance = new static(...$args);
        }
        return self::$_instance;
    }
}