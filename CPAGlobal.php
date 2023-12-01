<?php

use CPA\Core\ErrorHandler;
use CPA\Lib\l18n\l18n;
use CPA\Lib\PDObject;
use CPA\Lib\Input;

class CPAGlobal {

    /**
     * master casino資料庫
     * @var PDObject
     */
    public static $db;

    /**
     * slave casino資料庫(禁止寫入)
     * @var PDObject
     */
    public static $db2;

    /**
     * l18n object
     * @var l18n
     */
    public static $l18n;

    /**
     * Y-m-d H:i:s
     * @var string
     */
    public static $now_timestamp;

    /**
     * time()
     * @var int
     */
    public static $now_timesecond;

    /**
     * YYYYmm
     * @var int
     */
    public static $now_ym;

    /**
     * YYYYmm
     * @var int
     */
    public static $last_ym;
    public static $next_ym;

    /**
     * Y-m-d
     * @var string
     */
    public static $now_date;

    /**
     * day
     * @var int
     */
    public static $now_day;
    // debug
    public static $itdebug;
    // sitename
    public static $sitename;
    public static $WriteLogPath;
    public static $vardebug = false;
    public static $belong_site;
    //
    public static $SiteLang;

    const CPA_API_VERSION = '3.0';
    //
    public static $ignoreValidKeyHash = false;

    public static function _init() {
        self::$WriteLogPath = __DIR__ . '/log';
        $defaultTimezone = 'Asia/Taipei';
        if (isset(CPAConfig::$DefaultTimeZone) and CPAConfig::$DefaultTimeZone) {
            $defaultTimezone = CPAConfig::$DefaultTimeZone;
        }
        date_default_timezone_set($defaultTimezone);
        // ErrorHandler::Initial();
        self::$db = new PDObject(CPAConfig::$SYS_DB_CONFIG);
        self::$db2 = new PDObject(CPAConfig::$SYS_SLAVE_DB_CONFIG);
        self::$l18n = new l18n();
        self::$sitename = CPAConfig::$CPASitename;
        self::$now_timestamp = date("Y-m-d H:i:s");
        self::$now_timesecond = strtotime(self::$now_timestamp);
        self::$now_ym = date("Ym", self::$now_timesecond);
        $year = substr(self::$now_ym, 0, 4);
        $month = substr(self::$now_ym, 4, 2);
        if ($month - 1 == 0) {
            self::$last_ym = ($year - 1) . "12";
        } else {
            self::$last_ym = ($year) . str_pad($month - 1, 2, '0', STR_PAD_LEFT);
        }
        if ($month + 1 >= 13) {
            self::$next_ym = ($year + 1) . "01";
        } else {
            self::$next_ym = ($year) . str_pad($month + 1, 2, '0', STR_PAD_LEFT);
        }
        self::$now_date = date("Y-m-d", self::$now_timesecond);
        self::$now_day = date("d", self::$now_timesecond);
        self::$itdebug = Input::GetDigit("itdebug") == 1 ? true : false;
        if (self::$itdebug) {
            CPAConfig::$DebugMode = true;
        } else {
            CPAConfig::$DebugMode = false;
        }
        self::$vardebug = Input::GetDigit("vardebug") == 1 ? true : false;
        self::$SiteLang = Input::Get('SiteLang', 'TW');
        self::$ignoreValidKeyHash = CPAConfig::$DebugMode;
    }

    public static function SetFileFolder($var) {
        self::$GameFileFolder = $var;
    }
}
