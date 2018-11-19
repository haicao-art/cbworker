<?php
/**
 * Created by PhpStorm.
 * User: xiayiyun
 * Date: 2018/11/3
 * Time: 12:41
 */

return [
  'Channel' => 'Apis',
  'LOG_DIR' => 'Runtime/logs',
  'maxFiles' => 15,
  'level' => '100',
  'formatter' => 'Monolog\Formatter\JsonFormatter',
  'suffix' => 'log',
  'format' => "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
];
