<?php
# @Author: crababy
# @Date:   2018-03-25T09:23:52+08:00
# @Last modified by:   crababy
# @Last modified time: 2018-03-25T09:24:54+08:00
# @License: http://www.opensource.org/licenses/mit-license.php MIT License


$config = [
  'debug'   => true,

  'AppName'   => '',

  'namespace' => "Application\Iot\\",

  'util'     => [
    'record'      => true,
    'logPath'     => ROOT_PATH . '/Runtime/Logs/',
    'recordType'  => 'local',   //tcp local
    'address'     => '',
  ],

  'statistic'   => [
    'address'	=> 'udp://127.0.0.1:55656',	//api 请求 upd上报地址
  	'report'	=> true,			//是否开启上报
  ],

  'images'  => [
    'imageUrl'    => "http://images.demo.com/",
    'savePath'    => ''
  ],

  'mysql'   => [
    'host' => '127.0.0.1',
    'username' => 'root',
    'password' => '',
    'database_name' => 'crababy_shop'
  ],
  'redis' => [
    'host'    => '127.0.0.1',
    'port'    => '6379',
    'auth'    => 'CyouTest!Q@W1q2w',    //CyouTest!Q@W1q2w
    'db'      => 2,
    'prefix'  => ''
  ],

  'language'  => [
    'zh'    => [
      -4   => '图片上传失败',
      -3   => '更新数据失败!',
      -2   => '数据获取失败!',
      -1   => '系统异常，请联系管理员',
      0    => '成功',
      1002 => '类或方法不存在!',
      1004 => '请求参数错误',
      1010 => '图片上传失败',
      1011 => '图片格式不正确',

      1100	=> '登录已过期，请重新登录',

      1501  => '订单状态不正确',

    ],
    'en'    => [
      0    => 'success',
    ],
  ],
];
