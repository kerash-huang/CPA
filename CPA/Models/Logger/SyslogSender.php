<?php

namespace CPA\Models\Logger;

use CPA\Lib\SystemLog\SysLogger;
use CPA\Core\Config\SysLogConfig;

class SyslogSender {

    public $DEF_CF_SYSLOG_SERVER   = ''; //API路徑
    public $SYSLOG_FACILITY_VALUE  = 22;
    public $SYSLOG_SERVERITY_VALUE = 6;
    public $SYSLOG_SERVER_NAME     = ''; //依照不同使用 LIKE UFA_金流 , UFA_前台
    public $file_name              = '';

    function __construct($file_name = "you_forget_to_set_file_name", $SYSLOG_SERVER_NAME = '') {
        $DEF_SYSLOG_SERVER_NAME = SysLogConfig::SYSLOG_SERVER_NAME;
        $DEF_CF_SYSLOG_SERVER   = SysLogConfig::DEF_CF_SYSLOG_SERVER;

        $this->DEF_CF_SYSLOG_SERVER = $DEF_CF_SYSLOG_SERVER;
        $this->file_name            = $file_name;
        $this->SYSLOG_SERVER_NAME   = empty($SYSLOG_SERVER_NAME) ? $DEF_SYSLOG_SERVER_NAME : $SYSLOG_SERVER_NAME;
    }

    public function Send($msg) {

        //$msg = str_replace($msg , "\r\n" , "\r\n ");// 因syslog再轉換資料的時候 如果換行符號 緊接最後一個字原是小括號 parse會出錯 加個空格避免
        $msg    = $msg . " ";
        $syslog = new SysLogger($this->SYSLOG_FACILITY_VALUE, $this->SYSLOG_SERVERITY_VALUE, $this->SYSLOG_SERVER_NAME);
        return $syslog->Send($this->DEF_CF_SYSLOG_SERVER, $msg, $this->file_name);
    }
}
