<?php
/**
 * Created by PhpStorm.
 * User: xiayiyun
 * Date: 2018/11/3
 * Time: 11:15
 */

use Cbworker\Core\Application;


//初始化日志
$application = Application::getInstance();

$application->start();