<?php

class CPAConfig {

    public static $CPASitename = 'hex';
    public static $DebugMode = false;
    public static $CPA_DB_HOST = "127.0.0.1";
    public static $CPA_SLAVE_DB_HOST = "127.0.0.1";
    public static $CPA_DB_USER = "root";
    public static $CPA_DB_PASS = "admin";
    public static $CPA_DB_TABLE = "cpa_system";
    // 
    public static $CPA_AUTH_KEY = '';
    public static $CCPA_AUTH_KEY = '';
    //
    public static $SYS_DB_CONFIG = array("ip" => "127.0.0.1", "uid" => "root", "pwd" => "admin", "tbname" => "");
    public static $SYS_SLAVE_DB_CONFIG = array("ip" => "127.0.0.1", "uid" => "root", "pwd" => "admin", "tbname" => "");
    //
    public static $DefaultTimeZone = 'Asia/Taipei';
    public static $MonitorMethod = false;
}
