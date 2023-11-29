<?php

namespace CPA\Controller;

use CPA\Core\Base;
use CPA\Models\Network;
use CPAConfig;
use CPAGlobal;

class APIBase extends ControllerBase {

    public $remote_server_ip = "";
    public $remote_user_ip = "";
    public $cm, $wp, $csid, $wno;

    public function __construct(Base $base) {
        parent::__construct($base);

        $this->remote_server_ip = $base->remote_server_ip;
        $this->remote_user_ip = $this->pGet("ip");
        $this->cm = $this->pGetDigit("cm");
        $this->wp = $this->pGet("wp");
        $this->csid = $this->pGetDigit("csid");
        $this->wno = $this->pGetDigit("wno");
    }

    /**
     * 
     * @param string $job
     * @param string $Ljob
     * @param string $method
     * @param array $params
     * @param boolean $debug
     * @return string
     */
    public function CallWebAPI($job, $Ljob = '', $method = '', $params = array(), $debug = false) {
        $query = "job={$job}&Ljob={$Ljob}&method={$method}&" . http_build_query($params, '', '&');
        $query .= "&passwd=" . $this->pGet("token");
        $api_url = CPAConfig::$CasinoWebApi . "?" . $query;
        if ($debug) {
            echo "\\\\{$api_url}\n\n";
        }
        $api_result = Network::curl_get($api_url);
        return $api_result;
    }

    /**
     * 呼叫金流 cashflow_api.php
     * @param string $method
     * @param string $action
     * @param array $params
     * @return string
     */
    public function CallCfAPI($method, $action = '', $params = array()) {
        $query = "method={$method}&action={$action}&" . http_build_query($params, '', '&');
        $api_url = CPAConfig::$CasinoCashflowRoot . "cashflow_api.php?" . $query;
        $api_result = Network::curl_get($api_url);
        return $api_result;
    }

    /**
     * 呼叫金流 order.php
     * @param type $params
     * @return type
     */
    public function CallCfOrderAPI($params = array()) {
        $query = http_build_query($params, '', '&');
        $api_url = CPAConfig::$CasinoCashflowRoot . "order.php?" . $query;
        $api_result = Network::curl_get($api_url);
        return $api_result;
    }

    public function AuthMemberSnOrToken() {
        $member_sn = $this->pGetNumber("member_sn");
        $token = $this->pGet("token");
        if (!$member_sn and empty($token)) {
            $this->errParamLoss('member_sn, token');
        }
        $memberObject = CPAGlobal::$MemberObject;

        if ($token) {
            $member_id = $memberObject->GetMemberIdByToken($token);
            if (!$member_id) {
                $this->errTokenExpired();
            }
        } else {
            $member_id = $memberObject->GetMemberIdBySn($member_sn);
            if (!$member_id) {
                $this->errUserNotFound();
            }
        }
        return $member_id;
    }

    /**
     * 驗證用戶的 token 或資料
     * @return string
     */
    public function AuthMemberToken() {
        $token = $this->pGet("token");
        if (empty($token)) {
            $this->errParamLoss('token');
        }
        $memberObject = CPAGlobal::$MemberObject;
        $member_id = $memberObject->GetMemberIdByToken($token);
        if (!$member_id) {
            $this->errTokenExpired();
        }
        return $member_id;
    }

}
