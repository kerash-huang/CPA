<?php

namespace CPA\Lib;

use CPAGlobal;
use CPA\Lib\PDObject;
use CPA\Core\Response;
use CPA\Models\Network;
use CPAConfig;

class APIMonitor {

    /**
     *
     * @var PDObject 
     */
    private static $cpa_db;
    private static $cpa_slave_db;
    private static $is_start = false;
    private static $monitor_unk_uid = "";

    /**
     * time in second
     * @var time
     */
    private static $current_time = 0;

    /**
     * start up time
     * @var double 
     */
    private static $monitor_start_mtime = 0;

    /**
     * end up time
     * @var double
     */
    private static $monitor_end_mtime = 0;
    // controller and action in monitor
    private static $mon_controller, $mon_action;
    // table name
    private static $tb_TmpConcurrent = "tmp_concurrent",
        $tb_MonitorData = "monitor_method_data";

    /**
     * 啟用設定狀態
     * @param type $db  for cpa_system db
     */
    public static function start_up($db, $slave_db = null) {
        self::$is_start = true;
        self::$cpa_db = $db;
        if (!$slave_db) {
            self::$cpa_slave_db = $slave_db;
        } else {
            self::$cpa_slave_db = $db;
        }
        self::$current_time = time();
        self::$monitor_start_mtime = microtime(1);
        self::$monitor_unk_uid = uniqid(time() . ".", true);
    }

    public static function getCPADB() {
        return self::$cpa_db;
    }

    /**
     * 關閉 monitor
     */
    public static function no_monitor() {
        self::$is_start = false;
    }

    /**
     * 結束狀態 & 直接LOG
     */
    public static function end_up($success = false, $ResponseData = null) {
        if (!self::$is_start) {
            self::$monitor_start_mtime = microtime(1);
        }
        self::$monitor_end_mtime = microtime(1);
        self::EndUpLog($success, $ResponseData);
        self::$is_start = false;
        return round(self::$monitor_end_mtime - self::$monitor_start_mtime, 4);
    }

    /**
     * 清除 1 分鐘以前的平均統計值（for monitor table）
     */
    public static function Clear1MinMonitorData() {
        $last_one_min = date("Y-m-d H:i:s", strtotime("-1 min"));
        $sql = "UPDATE `" . self::$tb_MonitorData . "` SET `concurrent_in_min`=0, `average_during`=0, `last_during`=0 WHERE `last_call_datetime` <'{$last_one_min}'";
        self::$cpa_db->query($sql);
    }

    /**
     * 開始記錄
     * @param type $controller
     * @param type $action
     */
    public static function StartUpLog($controller, $action) {
        if (!self::$is_start or !CPAConfig::$MonitorMethod) {
            return;
        }
        self::$mon_controller = $controller;
        self::$mon_action = $action;
        self::InitMonitorData();
        self::KillOldConcurrent();
        self::AddTmpConcurrent();
        self::InitCallLog();
        self::$cpa_db->disconnect();
    }

    /**
     * 結束所有紀錄
     * @var boolean success 
     */
    private static function EndUpLog($success = false, $ResponseData = null) {
        if (!self::$is_start or !CPAConfig::$MonitorMethod) {
            return;
        }
        self::UpdateTmpConcurrent();
        self::UpdateMonitorData($success, $ResponseData);
        self::UpdateCallLog($success, $ResponseData);
    }

    /**
     * 初始化 monitor 資料功能
     */
    private static function InitMonitorData() {
        $sql = "INSERT INTO `" . self::$tb_MonitorData . "` ( controller , action , last_call_datetime ) VALUES (:controller, :action, '" . CPAGlobal::$now_timestamp . "' )"
            . " ON DUPLICATE KEY UPDATE `last_call_datetime` = '" . CPAGlobal::$now_timestamp . "' ";
        self::$cpa_db->bind_query($sql, array(":controller" => self::$mon_controller, ":action" => self::$mon_action));
    }

    /**
     * 新增 api call log 資料
     */
    private static function InitCallLog() {
        $tableName = self::CreateTable_ApiCallLog();
        $get_param = filter_input_array(INPUT_GET);
        $post_param = filter_input_array(INPUT_POST);

        $caller_ip = Network::GetCallerRealIP();
        $param = array(
            "call_unk_id" => self::$monitor_unk_uid,
            "controller" => self::$mon_controller,
            "action" => self::$mon_action,
            "get_param" => json_encode($get_param),
            "post_param" => json_encode($post_param),
            "call_datetime" => date("Y-m-d H:i:s"),
            "caller_ip" => $caller_ip
        );
        self::$cpa_db->insert($tableName, $param);
    }

    /**
     * 寫入呼叫 log
     * @param type $success
     * @param type $ResponseData
     */
    private static function UpdateCallLog($success = false, $ResponseData = null) {
        $tableName = self::CreateTable_ApiCallLog();
        $last_during = round(self::$monitor_end_mtime - self::$monitor_start_mtime, 4);
        $update_call_log = "UPDATE `{$tableName}` SET `during_mtime`={$last_during}, `last_result`=:success, `return_result`=:last_str WHERE call_unk_id=:unk_id and controller=:controller and action=:action ";
        self::$cpa_db->bind_query($update_call_log, array(":success" => $success ? 1 : 0, ":last_str" => json_encode($ResponseData), ":unk_id" => self::$monitor_unk_uid, ":controller" => self::$mon_controller, ":action" => self::$mon_action));
    }

    /**
     * 更新最後監控資料
     * @param type $success
     * @param type $ResponseData
     */
    private static function UpdateMonitorData($success = false, $ResponseData = null) {
        $get_param = filter_input_array(INPUT_GET);
        $post_param = filter_input_array(INPUT_POST);
        $success_condition = "";
        if (!$success) {
            $success_condition = "`fail_times` = `fail_times` +1";
        } else {
            $success_condition = "`success_times` = `success_times` +1";
        }
        $last_during = round(self::$monitor_end_mtime - self::$monitor_start_mtime, 4);

        $get_min_concurrent = self::$cpa_slave_db->selectone(" (sum(`call_times`)/60) as `concurrent` ", self::$tb_TmpConcurrent, " call_second > " . (self::$current_time - 60) . " AND `controller`='" . self::$mon_controller . "' AND `action`='" . self::$mon_action . "'");
        $min_concurrent = 0;
        if ($get_min_concurrent["concurrent"]) {
            $min_concurrent = $get_min_concurrent["concurrent"];
        }
        $min_concurrent = ceil($min_concurrent);

        $get_avg_during = self::$cpa_slave_db->selectone(" (sum(`avg_during`)/sum(`call_times`)) as `avg`", self::$tb_TmpConcurrent, " call_second > " . (self::$current_time - 60) . " AND `controller`='" . self::$mon_controller . "' AND `action`='" . self::$mon_action . "'");
        $avg_during = 0;
        if ($get_avg_during["avg"]) {
            $avg_during = $get_avg_during["avg"];
        }

        $caller_ip = Network::GetCallerRealIP();

        $sql = "UPDATE `" . self::$tb_MonitorData . "` SET {$success_condition} , `concurrent_in_min`={$min_concurrent} ,`last_during`={$last_during}, `average_during`={$avg_during}, "
            . " last_get_param =:last_get, last_post_param=:last_post, last_return_result=:last_return, last_result= :last_result , last_caller_ip = :last_ip, is_cache= " . (Response::$IsCache ? 1 : 0)
            . " WHERE `controller`=:controller and `action`=:action";
        self::$cpa_db->bind_query($sql, array(
            ":controller" => self::$mon_controller, ":action" => self::$mon_action,
            ":last_get" => json_encode($get_param), ":last_post" => json_encode($post_param), ":last_return" => json_encode($ResponseData), ":last_result" => $success ? 1 : 0, ":last_ip" => $caller_ip
        ));
    }

    /**
     * 清除過久紀錄(只是為了計算 concurrent，不用一直保留)
     * @return type
     */
    private static function KillOldConcurrent() {
        $old_time_log = strtotime("-1 hours");
        $sql = "DELETE FROM `" . self::$tb_TmpConcurrent . "` WHERE `controller`='" . self::$mon_controller . "' AND `action`='" . self::$mon_action . "' AND `call_second` < {$old_time_log}";
        self::$cpa_db->query($sql);
    }

    /**
     * 寫入 tmp concurrent 表
     * @param type $controller
     * @param type $action
     * @return type
     */
    private static function AddTmpConcurrent() {
        $sql = "INSERT INTO `" . self::$tb_TmpConcurrent . "` (controller, action, call_second, call_times, avg_during)"
            . " VALUES (:controller, :action, " . self::$current_time . ", 1, 0)"
            . " ON DUPLICATE KEY UPDATE `call_times` = `call_times` +1 ";
        self::$cpa_db->bind_query($sql, array(":controller" => self::$mon_controller, ":action" => self::$mon_action));
    }

    /**
     * 更新 tmp concurrent 表紀錄
     */
    private static function UpdateTmpConcurrent() {
        $during_mtimes = self::$monitor_end_mtime - self::$monitor_start_mtime;
        $sql = "UPDATE `" . self::$tb_TmpConcurrent . "` SET `avg_during` = ((`call_times`-1)*`avg_during`+{$during_mtimes})/`call_times` WHERE controller=:controller AND action =:action AND call_second = " . self::$current_time;
        self::$cpa_db->bind_query($sql, array(":controller" => self::$mon_controller, ":action" => self::$mon_action));
    }

    /**
     * 取得監控資料總表
     * @return array
     */
    public static function GetMonitorDataLog() {
        $sql = "SELECT * FROM `" . self::$tb_MonitorData . "`";
        $data = self::$cpa_slave_db->query($sql);
        if (!$data) {
            $data = array();
        }
        return $data;
    }

    public static function GetMethodDataLog($Date, $controller = '', $action = '') {
        $tableName = "api_call_log_" . str_replace("-", "", $Date);
        $condition = [];
        if ($controller) {
            $condition["controller"] = $controller;
        }
        if ($action) {
            $condition["action"] = $action;
        }
        $data = self::$cpa_slave_db->select(array("controller, action, during_mtime, get_param, post_param, return_result, call_datetime, last_result, caller_ip"), $tableName, $condition, "order by call_datetime desc", " limit 100");
        return $data;
    }

    /**
     * 建立 Log 表
     */
    private static $apiCallLogTable = "";

    private static function CreateTable_ApiCallLog() {
        if (self::$apiCallLogTable != "") {
            return self::$apiCallLogTable;
        }
        $ym = date("Ym");
        self::$apiCallLogTable = "api_call_log_" . $ym;
        $check_sql = "SHOW TABLES LIKE '" . self::$apiCallLogTable . "'";
        if (!self::$cpa_db->query($check_sql)) {
            $sql = "CREATE TABLE IF NOT EXISTS `" . self::$apiCallLogTable . "` (
                `call_unk_id` varchar(80) NOT NULL,
                `controller` varchar(50) NOT NULL,
                `action` varchar(50) NOT NULL,
                `during_mtime` decimal(14,4) NOT NULL DEFAULT '0.0000',
                `get_param` text,
                `post_param` text,
                `return_result` text,
                `call_datetime` datetime DEFAULT NULL,
                `last_result` int(1) NOT NULL DEFAULT '0',
                `caller_ip` varchar(20) DEFAULT NULL,
                UNIQUE KEY `call_unk_id` (`call_unk_id`),
                KEY `controller` (`controller`,`action`)
              ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
              ";
            self::$cpa_db->query($sql);
        }
        return self::$apiCallLogTable;
    }
}
