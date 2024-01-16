<?php

namespace CPA\Core;

use CPA\Lib\APIMonitor;
use CPAGlobal;

class Response {
    public static $ExternalMessage = array();
    public static $IsCache = 0;
    public static $CacheDatetime = "";
    public static $MessageLangCode = '';
    private $IsSuccess = false;
    private $Message = "";
    private $Value = null;
    private $ErrorCode = 0;
    private $HttpCode = 200;
    private $RDError = '';

    function __construct() {
    }

    /**
     * 
     * @param type $message
     * @param type $value
     * @return $this
     */
    public function Success($message = '', $value = null) {
        $this->IsSuccess = true;
        $this->Message = $message;
        $this->Value = $value;
        return $this;
    }

    /**
     * 
     * @param type $error_code
     * @param type $message
     * @return $this
     */
    public function Fail($error_code, $message) {
        $this->IsSuccess = false;
        $this->ErrorCode = $error_code;
        $this->Message = $message;
        $this->RDError = debug_backtrace();
        return $this;
    }

    /**
     * 
     * @param type $code
     */
    public function HttpCode($code = 200) {
        if ($code != 200) {
        }
    }

    public function Show() {
        $ResponseData = array(
            "IsSuccess" => $this->IsSuccess,
            "Message" => $this->Message,
            "Value" => $this->Value,
            "ErrorCode" => $this->ErrorCode !== 0 ? "" . $this->ErrorCode : 0,
            'Version' => \CPAGlobal::$CPA_API_VERSION,
        );
        if (!empty(Response::$MessageLangCode)) {
            $ResponseData['MsgLangCode'] = Response::$MessageLangCode;
        }
        $during_time = APIMonitor::end_up($this->IsSuccess, $ResponseData);
        $ResponseData["SpendTime"] = $during_time;
        $ResponseData["ServerTime"] = date("Y-m-d H:i:s");
        if (Response::$IsCache) {
            $ResponseData["Cache"] = true;
            $ResponseData["CacheDatetime"] = Response::$CacheDatetime;
        }
        if (CPAGlobal::$itdebug) {
            $ResponseData["DebugMessage"] = $this->RDError;
        }
        if (CPAGlobal::$vardebug) {
            $ResponseData["Var"] = array(
                "POST" => filter_input_array(INPUT_POST),
                "GET" => filter_input_array(INPUT_GET),
            );
        }
        if (Response::$ExternalMessage) {
            $ResponseData = array_merge($ResponseData, Response::$ExternalMessage);
        }
        echo json_encode($ResponseData);
        return $this;
    }

    public function End() {
        die();
    }
}
