<?php

# @Author: crababy
# @Date:   2018-03-23T17:42:16+08:00
# @Last modified by:   crababy
# @Last modified time: 2018-03-23T17:49:01+08:00
# @License: http://www.opensource.org/licenses/mit-license.php MIT License

namespace Application\Iot\Controller;

use Cbworker\Library\Helper;
/**
 * index
 */
class Index extends Base {

  public function index($req, &$rsp) {
    $rsp['data'] = 'this is index message';
    return 0;
  }

  public function download($req, &$rsp) {
    $header  = "HTTP/1.1 200 OK\r\n";
    $header .= "Content-Type: application/vnd.ms-excel\r\n";
    $header .= "Connection: keep-alive\r\n";
    $header .= "Content-Disposition: attachment;filename=dddd.csv\r\n";
    $header .= "Cache-Control: max-age=0\r\n";
    $header .= "Pragma: no-cache;\r\n";
    $header .= "Transfer-Encoding: chunked\r\n";
    //Http::end('商户编号');
    //$len = strlen('debug,') + strlen('demo');
    //$header .= "Content-Length: ". $len ."\r\n\r\n";
    $header .= "\r\n";
    $this->application->conn->send($header, true);
    ob_start();
    $fp = fopen('php://output', 'a');
    $header1 = ['订单ID','用户手机','姓名','订单状态', '订单金额', '调度费','支付金额','利润金额','开始时间','结束时间','骑行时间','骑行时间(秒)','骑行里程', '结束来源','车辆编号','所属辖区', '二级辖区'];
    foreach ($header1 as $i => $v) {
        $header1[$i] = iconv('utf-8', 'gbk', $v);
    }
    fputcsv($fp, $header1);
    $content = ob_get_contents();
    $length = dechex(strlen($content));
    $this->application->conn->send("{$length}\r\n{$content}\r\n", true);

    ob_clean();   //清空（擦掉）输出缓冲区
    $data = ['123', '456'];

    echo "fdsaf,fdsfd\r\n";
    echo "fdsaf1111,fdsfd1111\r\n";

    foreach ($data as $key => $value) {
      $order[] = $value;
    }
    fputcsv($fp, $order);
    $content1 = ob_get_contents();    //返回输出缓冲区的内容
    $length1 = dechex(strlen($content1));
    $this->application->conn->send("{$length1}\r\n{$content1}\r\n", true);

    $this->application->conn->send("0\r\n", true);
    $this->application->conn->send("\r\n", true);
    ob_end_clean();   //清空（擦除）缓冲区并关闭输出缓冲
    fclose($fp);
    return 0;
  }

}
