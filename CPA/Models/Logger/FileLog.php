<?php

namespace CPA\Models\Logger;

use CPAGlobal;
use CPA\Models\Logger\SyslogSender;

class FileLog {

    public static function WriteTextLog($log_name, $log_text) {
        file_put_contents(CPAGlobal::$WriteLogPath . 'Log_' . $log_name . '_' . date('Ymd') . '.txt', '[' . date('His') . ']' . $log_text . "\n", FILE_APPEND);
        return true;
    }

    public static function WriteSysLog($log_name, $log_text) {
        try {
            $slsender = new SyslogSender(CPAGlobal::$sitename . (CPAGlobal::$belong_site ? '_' . CPAGlobal::$belong_site : '') . '_' . $log_name . '_' . date("Ymd"));
            $slsender->Send('[' . date('His') . ']' . $log_text);
        } catch (\Exception $e) {
        }
        return true;
    }
}
