<?php

namespace CPA\Core;

use Exception;

class ErrorHandler {

    const CE_FATAL = E_ERROR | E_USER_ERROR | E_CORE_ERROR | E_COMPILE_ERROR | E_RECOVERABLE_ERROR | E_PARSE;
    const CE_WARNING = E_WARNING | E_USER_WARNING | E_CORE_WARNING | E_COMPILE_WARNING;
    const CE_NOTICE = E_NOTICE | E_USER_NOTICE;
    const CE_OTHER = E_STRICT | E_DEPRECATED | E_USER_DEPRECATED;

    private static $error_list = array();

    public static function Initial() {
        set_error_handler(array('CPA\Core\ErrorHandler', 'System'));
        register_shutdown_function(array('CPA\Core\ErrorHandler', 'FatalHandler'));
    }

    // @return bool false 將會使用PHP內建的handler，true會跳過PHP內建的handler
    public static function System($err_no, $err_str, $err_file, $err_line) {
        $is_fatal = ($err_no === ($err_no & self::CE_FATAL));
        $is_warning = ($err_no === ($err_no & self::CE_WARNING));
        $is_notice = ($err_no === ($err_no & self::CE_NOTICE));
        $is_other = ($err_no === ($err_no & self::CE_OTHER));
        if (!(error_reporting() & $err_no)) {
            // 過濾掉不屬於error_reporting()的錯誤，目前只抓正規的
            return false;
        }

        $err_type = ($is_fatal ? 'Fatal' :
                ($is_warning ? 'Warning' :
                ($is_notice ? 'Notice' :
                ($is_other ? 'Other' : 'Else')
                )
                )
                );
        array_push(self::$error_list, "PHPCode {$err_type} error: {$err_str} in {$err_file} on line {$err_line}");
        $err = "PHPCode {$err_type} error: {$err_str} in {$err_file} on line {$err_line}";
        \CPA\Lib\ErrorLogger::WriteLog($err, $err_type);
        if ($is_fatal) { // fatal error 时会把结果也正确回传给前台，以免造成错误
            try {
                Response::$ExternalMessage = array('SYSTEM_ERROR' => $err);
                throw new \Exception('System processing fail, please retry again. 系统处理错误，请重新尝试操作', 9500);
            } catch (Exception $ex) {
                (new Response())->Fail($ex->getCode(), $ex->getMessage())->Show();
            }
            exit(1);
        }

        return false;
    }

    // 用來抓取fatal error * php7 就不用了。 因為5的set_error_handler抓不到才寫的
    public static function FatalHandler() {
        $error = error_get_last();
        if ($error && ($error["type"] === ($error["type"] & self::CE_FATAL))) {
            $err_no = $error["type"];
            $err_file = $error["file"];
            $err_line = $error["line"];
            $err_str = $error["message"];
            self::System($err_no, $err_str, $err_file, $err_line);
        }
    }

    public static function GetErrorRecords() {
        return implode(PHP_EOL . PHP_EOL, self::$error_list);
    }

}
