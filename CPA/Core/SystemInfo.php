<?php

namespace CPA\Core;

use CPAConfig;

class SystemInfo {

    public static function GetSystemKey($key) {
        return CPAConfig::$CPA_AUTH_KEY;
    }

    public static function testAviTimeLocker($datefrom, $dateend = null, $user = null, $id_list = null, $force = false) {
        if($force) {
            return true;
        }
        if ($user) {
            if ($user['testuser']) {
                return true;
            }
            if (is_array($id_list)) {
                if (in_array($user['member_id'], $id_list)) {
                    return true;
                }
            }
        }
        if (\CPAGlobal::$now_timestamp >= $datefrom) {
            if ($dateend) {
                if ($dateend >= \CPAGlobal::$now_timestamp) {
                    return true;
                }
            } else {
                return true;
            }
        }
        return false;
    }

}
