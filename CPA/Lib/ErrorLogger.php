<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace CPA\Lib;

use CPAGlobal;

class ErrorLogger {

    const LOG_TABLE_NAME = 'cpa_api_error_log';

    public static function WriteLog($err_message, $error_type = '') {
        $get_param = filter_input_array(INPUT_GET);
        $post_param = filter_input_array(INPUT_POST);
        $url = filter_input(INPUT_SERVER, 'REQUEST_URI');
        $tableName = self::GetTable(true);
        \CPAGlobal::$db->insert($tableName, array('log_url' => $url, 'get_param' => json_encode($get_param), 'post_param' => json_encode($post_param), 'error_message' => $err_message, 'error_type' => $error_type, 'add_datetime' => CPAGlobal::$now_timestamp));
    }

    public static function GetTable($ignoreCreate = false) {
        $query = 'CREATE TABLE IF NOT EXISTS `' . ErrorLogger::LOG_TABLE_NAME . '` (
            `log_sn` int(11) NOT NULL AUTO_INCREMENT,
            `log_url` varchar(300) DEFAULT NULL,
            `get_param` text,
            `post_param` text,
            `error_message` text,
            `error_type` varchar(50) DEFAULT NULL,
            `add_datetime` datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`log_sn`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ';
        if (!$ignoreCreate) {
            \CPAGlobal::$db->query($query);
        }
        return ErrorLogger::LOG_TABLE_NAME;
    }
}
