<?php

namespace CPA\Core;

use CPA\Models\Network;
use Exception;

class Base {

    // site debug 狀態
    public $api_debug_mode = false;
    // api debug 參數
    public $api_param_debug_mode = false;
    // 是不是要 monitor 紀錄
    public $monitor_log_mode = true;
    // 呼叫方法
    public $request_method = null;
    // 接口呼叫 domain
    public $request_domain = "";
    public $remote_server_ip = "";

    /**
     * @var Routing
     */
    public $routing = null;

    function __construct() {
        $this->request_method = strtolower(filter_input(INPUT_SERVER, "REQUEST_METHOD"));
        $this->routing = new Routing();
        $this->request_domain = filter_input(INPUT_GET, "cpa_host");
        if (!$this->api_debug_mode) {
            if (!\CPAGlobal::$ignoreValidKeyHash) {
                $this->valid_request_site();
            }
        }
        $this->remote_server_ip = Network::GetCallerRealIP();
        
    }

    function valid_request_site() {
        $cpa_hash = filter_input(INPUT_GET, "cpa_key_hash");
        if (!$cpa_hash) {
            throw new Exception("CPA System auth fail");
        }
        $key = SystemInfo::GetSystemKey($this->request_domain);
        if ($key != $cpa_hash) {
            throw new Exception("CPA System auth fail");
        }
    }

}
