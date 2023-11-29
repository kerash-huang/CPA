<?php

namespace CPA\Lib;

class Input {

    /**
     * 
     * @param string $indexKey
     * @param string $defaultValue
     * @return string
     */
    public static function Get($indexKey, $defaultValue = '') {
        $value = filter_input(INPUT_GET, $indexKey);
        if (trim($value) === '') {
            $value = $defaultValue;
        }
        return $value;
    }

    /**
     * 
     * @param string $indexKey
     * @param string $defaultValue
     * @return string
     */
    public static function Post($indexKey, $defaultValue = '') {
        $value = filter_input(INPUT_POST, $indexKey);
        if (trim($value) == '') {
            $value = $defaultValue;
        }
        return $value;
    }

    /**
     * 
     * @param string $indexKey
     * @param string $defaultValue
     * @return int|float
     */
    public static function GetDigit($indexKey, $defaultValue = 0) {
        $value = filter_input(INPUT_GET, $indexKey);
        if (!preg_match("/\d+(\.\d+)?/", $value)) {
            $value = $defaultValue;
        }
        return $value;
    }

    /**
     * 
     * @param string $indexKey
     * @param string $defaultValue
     * @return int|float
     */
    public static function PostDigit($indexKey, $defaultValue = 0) {
        $value = filter_input(INPUT_POST, $indexKey);
        if (!preg_match("/\d+(\.\d+)?/", $value)) {
            $value = $defaultValue;
        }
        return $value;
    }

}
