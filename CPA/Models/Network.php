<?php

namespace CPA\Models;

use CPA\Models\Logger\FileLog;
use CPAGlobal;

class Network {

    // last call time diff second
    public static $last_execute_time = 0;
    public static function GetCallerRealIP() {
        if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            if (strpos($_SERVER['HTTP_X_FORWARDED_FOR'], ',') > 0) {
                $addr = explode(",", $_SERVER['HTTP_X_FORWARDED_FOR']);
                return trim($addr[0]);
            } else {
                return $_SERVER['HTTP_X_FORWARDED_FOR'];
            }
        } else {
            return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'local-call';
        }
    }

    /**
     *
     * @param string $url
     * @param type $param
     * @param type $timeout
     * @param type $write_log
     * @return boolean
     */
    public static function curl_post($url, $param, $timeout = 10, $write_log = false) {
        if (substr($url, 0, 4) != "http") {
            $url = "http://" . $url;
        }

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_ENCODING, "UTF-8");
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $param);
        if (substr($url, 0, 5) == "https") {
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        }
        self::ShutdownDb();
        $start_ms_time = microtime(1);
        $response = curl_exec($curl);
        $end_ms_time = microtime(1);
        self::$last_execute_time = $end_ms_time - $start_ms_time;
        if (curl_errno($curl) or !$response) {
            $err_message = "[" . round($end_ms_time - $start_ms_time, 2) . "] [URL] {$url}\n[ERROR] " . curl_error($curl) . " [PARAM] ".json_encode($param) . "\n";
            FileLog::WriteTextLog('CURL_POST_ERROR', $err_message);
            FileLog::WriteSysLog('CURL_POST_FAIL', $err_message);
            curl_close($curl);
            return false;
        }
        if ($end_ms_time - $start_ms_time > 3) {
            $err_message = "[" . round($end_ms_time - $start_ms_time, 2) . "] [URL]{$url}\n[QUERY] " . var_export($param, true) . "\n[RESP]" . $response . "\n";
            FileLog::WriteTextLog('CURL_POST_SLOW', $err_message);
            FileLog::WriteSysLog('CURL_POST_SLOW', $err_message);
        }
        if ($write_log) {
            $err_message = "[" . round($end_ms_time - $start_ms_time, 2) . "] [URL]{$url}\n[QUERY] " . var_export($param, true) . "\n[RESP]" . $response . "\n";
            FileLog::WriteTextLog('CURL_POST', $err_message);
            FileLog::WriteSysLog('CURL_POST', $err_message);
        }
        curl_close($curl);
        return $response;
    }

    /**
     *
     * @param string $url
     * @param integer $timeout
     * @param boolean $write_log
     * @return boolean
     */
    public static function curl_get($url, $timeout = 5, $write_log = false) {
        if (substr($url, 0, 4) != "http") {
            $url = "http://" . $url;
        }
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_ENCODING, "UTF-8");
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

        if (substr($url, 0, 5) == "https") {
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        }
        self::ShutdownDb();
        $start_ms_time = microtime(1);
        $response = curl_exec($curl);
        $end_ms_time = microtime(1);
        self::$last_execute_time = $end_ms_time - $start_ms_time;
        if (curl_errno($curl) or !$response) {
            $err_message = "[" . round($end_ms_time - $start_ms_time, 2) . "] [URL]{$url}\n[ERROR] " . curl_error($curl) . "\n";
            FileLog::WriteTextLog('CURL_GET_ERROR', $err_message);
            FileLog::WriteSysLog('CURL_GET_ERROR', $err_message);
            curl_close($curl);
            return null;
        }
        if ($end_ms_time - $start_ms_time > 3) {
            $err_message = "[" . round($end_ms_time - $start_ms_time, 2) . "] [URL]{$url}\n[RESP] " . $response . "\n";
            FileLog::WriteTextLog('CURL_GET_SLOW', $err_message);
            FileLog::WriteSysLog('CURL_GET_SLOW', $err_message);
        }
        if ($write_log) {
            $err_message = "[" . round($end_ms_time - $start_ms_time, 2) . "] [URL]{$url}\n[RESP] " . $response . "\n";
            FileLog::WriteTextLog('CURL_GET', $err_message);
            FileLog::WriteSysLog('CURL_GET', $err_message);
        }
        curl_close($curl);
        return $response;
    }

    /**
     * CURL POST JSON BODY
     *
     * @param string $url
     * @param string $json_body
     * @param integer $timeout
     * @param boolean $write_log
     * @return mixed
     */
    public static function curl_postJson($url, $json_body, $timeout = 10, $write_log = false) {
        if (substr($url, 0, 4) != "http") {
            $url = "http://" . $url;
        }
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_ENCODING, "UTF-8");
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        if (substr($url, 0, 5) == "https") {
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        }
        curl_setopt($curl, CURLOPT_POSTFIELDS, $json_body);
        self::ShutdownDb();

        $start_ms_time = microtime(1);
        $response = curl_exec($curl);
        $end_ms_time = microtime(1);

        self::$last_execute_time = $end_ms_time - $start_ms_time;
        if (curl_errno($curl) or !$response) {
            $err_message = "[" . round($end_ms_time - $start_ms_time, 2) . "] [URL]{$url}\n[BODY] ".$json_body." \n[ERROR] " . curl_error($curl) . "\n";
            FileLog::WriteTextLog('CURL_POSTJ_ERROR', $err_message);
            FileLog::WriteSysLog('CURL_POSTJ_ERROR', $err_message);
            curl_close($curl);
            return false;
        }
        if ($end_ms_time - $start_ms_time > 3) {
            $err_message = "[" . round($end_ms_time - $start_ms_time, 2) . "] [URL]{$url}\n[BODY] ".$json_body." \n[RESP] " . $response . "\n";
            FileLog::WriteTextLog('CURL_POSTJ_SLOW', $err_message);
            FileLog::WriteSysLog('CURL_POSTJ_SLOW', $err_message);
        }
        if ($write_log) {
            $err_message = "[" . round($end_ms_time - $start_ms_time, 2) . "][URL]{$url}\n[BODY] ".$json_body." \n[RESP]" . $response . "\n";
            FileLog::WriteTextLog('CURL_POSTJ', $err_message);
            FileLog::WriteSysLog('CURL_POSTJ', $err_message);
        }
        curl_close($curl);
        return $response;
    }

    /**
     * Internal api call
     *
     * @param string $method
     * @param array $get_param
     * @param array $post_param
     * @return mixed
     */
    public static function InternalApiCall($method, $get_param, $post_param = null, $write_log = false) {
        $base_url = 'http://127.0.0.1:81/PublicCasinoApi/index.php?method=' . $method . '&cpa_key_hash=' . \CPAConfig::$CPA_AUTH_KEY;
        $base_url .= '&' . http_build_query($get_param, '', '&');
        if ($post_param) {
            $response = self::curl_post($base_url, $post_param, 10, $write_log);
        } else {
            $response = self::curl_get($base_url, 10, $write_log);
        }
        if (!$response) {
            return null;
        }
        $respData = json_decode($response, true);
        return $respData;
    }

    public static function ShutdownDb() {
        CPAGlobal::$db->disconnect();
        CPAGlobal::$db2->disconnect();
        CPAGlobal::$mdb->disconnect();
        CPAGlobal::$mdb2->disconnect();
    }
}
