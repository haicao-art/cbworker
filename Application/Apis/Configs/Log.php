<?php
/**
 * Created by PhpStorm.
 * User: xiayiyun
 * Date: 2018/11/3
 * Time: 12:41
 */

return [
  'Type'          => 'CLogger',
  'LOG_DIR'       => 'Runtime/logs',
  'ClearTime'     => '1296000',
  'level'         => '100',
  'LogLevel'      => 'ERROR,DEBUG,INFO',
  'format'        => "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n"
];
