<?php

namespace CPA\Controller;

use CPA\Core\Base;

class ControllerBase extends ExceptionBase {

    private $input_get_param = array();
    private $input_post_param = array();

    public function __construct(Base $base) {
        $this->input_get_param = filter_input_array(INPUT_GET);
        $this->input_post_param = filter_input_array(INPUT_POST);
        if ($base->api_param_debug_mode) {
            $this->input_post_param = array_merge((array) $this->input_get_param, (array) $this->input_post_param);
        }
    }

    /**
     * 
     * @param string $text
     * @param string $name
     * @return boolean
     */
    public function checkNotEmpty($text, $name) {
        $clean = trim($text);
        $clean = str_replace("\\n", "", $clean);
        if (mb_strlen($clean) == '') {
            $this->errParamLoss($name);
        }
        return true;
    }

    /**
     * 
     * @param decimal $amount
     * @param string $name
     * @return boolean
     */
    public function checkLTZero($amount, $name) {
        if ($amount <= 0) {
            $this->errLTZero($name);
        }
        return true;
    }

    /**
     * 檢查必填欄位
     * @param array $params
     * @return boolean
     */
    public function checkRequirePost($params = array()) {
        foreach ($params as $key) {
            if (!isset($this->input_post_param[$key]) or $this->input_post_param[$key] == '') {
                $this->errParamLoss($key);
            } else {
                continue;
            }
        }
        return true;
    }

    /**
     * 
     * @param string $text
     * @param iint $min
     * @param int $max
     * @param string $n
     */
    public function checkTextLength($text, $min, $max = 0, $n = '') {
        if ($max == 0) {
            $max = $min + 1;
        }
        $textLength = mb_strlen($text, 'utf-8');
        if ($textLength < $min || $textLength > $max) {
            $this->errDataLength($n);
        }
    }
    /**
     * check enum value
     *
     * @param string $text
     * @param array $hastack
     * @return void
     */
    public function checkEnumData($text, $hastack) {
        if (!in_array($text, (array)$hastack)) {
            $this->errDataType();
        }
    }

    /**
     * check normal char with no special char
     *
     * @param string $text
     * @param string $name
     * @return void
     */
    public function checkCharOnly($text, $name = '') {
        if (!preg_match("/^[a-zA-Z0-9]+$/", $text)) {
            $this->errCharOnly($name);
        }
    }

    /**
     * check only 0-9
     *
     * @param string $char
     * @return void
     */
    public function checkDigitOnly($char) {
        if (!preg_match("/^[0-9]+$/", $char)) {
            $this->errDigitOnly();
        }
    }

    /**
     * get pure var value
     *
     * @param string $key
     * @param string $default
     * @return mixed
     */
    public function pGet($key, $default = '') {
        if (isset($this->input_get_param[$key])) {
            return $this->input_get_param[$key];
        } else {
            return $default;
        }
    }

    /**
     * get encoded char
     *
     * @param string $key
     * @param string $default
     * @return mixed
     */
    public function pGetText($key, $default = '') {
        if (isset($this->input_get_param[$key])) {
            return htmlentities($this->input_get_param[$key], ENT_QUOTES, "utf-8");
        } else {
            return $default;
        }
    }

    /**
     * get numberic char
     *
     * @param string $key
     * @param float $default
     * @return mixed|int
     */
    public function pGetNumber($key, $default = 0) {
        if (isset($this->input_get_param[$key])) {
            if (preg_match("/^\d+$/", $this->input_get_param[$key])) {
                return $this->input_get_param[$key];
            } else {
                return $default;
            }
        } else {
            return $default;
        }
    }

    /**
     * get decimal char
     *
     * @param string $key
     * @param float $default
     * @return float
     */
    public function pGetDigit($key, $default = 0) {
        if (isset($this->input_get_param[$key])) {
            if (preg_match("/^\-?\d+(\.\d+)?$/", $this->input_get_param[$key])) {
                return $this->input_get_param[$key];
            } else {
                return $default;
            }
        } else {
            return $default;
        }
    }

    public function pGetDate($key, $default = '') {
        if (isset($this->input_get_param[$key])) {
            $date_info = explode("-", $this->input_get_param[$key]);
            if (count($date_info) != 3) {
                return null;
            }
            $year = $date_info[0];
            $month = $date_info[1];
            $day = $date_info[2];

            //            if (checkdate($month, $day, $year)) {
            if ($month >= 1 and $month <= 12 and $day >= 1 and $day <= 31) {
                return $this->input_get_param[$key];
            } else {
                return $default;
            }
        } else {
            return $default;
        }
    }

    public function pGetDateTime($key, $date_append = "00:00:00") {
        if (isset($this->input_get_param[$key])) {
            $valid_date = date_create($this->input_get_param[$key]);
            if ($valid_date) {
                return date_format($valid_date, "Y-m-d H:i:s");
            }
            $only_date = $this->pGetDate($key);
            if ($only_date) {
                return $only_date . " " . $date_append;
            }
            return null;
        } else {
            return null;
        }
    }

    /**
     * 純文字
     * @param string $key
     * @param string $default
     * @return string
     */
    public function pPost($key, $default = '') {
        if (isset($this->input_post_param[$key])) {
            return urldecode($this->input_post_param[$key]);
        } else {
            return $default;
        }
    }

    /**
     * 有使用 html encode的文字
     * @param type $key
     * @param type $default
     * @return type
     */
    public function pPostText($key, $default = '') {
        if (isset($this->input_post_param[$key])) {
            return htmlentities(urldecode($this->input_post_param[$key]), ENT_QUOTES, "utf-8");
        } else {
            return $default;
        }
    }

    public function pPostDate($key, $default = '') {
        if (isset($this->input_post_param[$key])) {
            $date_info = explode("-", $this->input_post_param[$key]);
            if ($date_info != 3) {
                return null;
            }
            $year = $date_info[0];
            $month = $date_info[1];
            $day = $date_info[2];
            if (checkdate($month, $day, $year)) {
                return $this->input_post_param[$key];
            } else {
                return $default;
            }
        } else {
            return $default;
        }
    }

    public function pPostDateTime($key, $date_append = "00:00:00") {
        if (isset($this->input_post_param[$key])) {
            $valid_date = date_create($this->input_post_param[$key]);
            if ($valid_date) {
                return date_format($valid_date, "Y-m-d H:i:s");
            }
            if ($only_date = $this->pPostDate($key)) {
                return $only_date . " " . $date_append;
            }
            return null;
        } else {
            return null;
        }
    }

    public function pPostDigit($key, $default = 0) {
        if (isset($this->input_post_param[$key])) {
            if (preg_match("/^\-?\d+(\.\d+)?$/", $this->input_post_param[$key])) {
                return $this->input_post_param[$key];
            } else {
                return $default;
            }
        } else {
            return $default;
        }
    }

    public function pPostNumber($key, $default = 0) {
        if (isset($this->input_post_param[$key])) {
            if (preg_match("/^\d+$/", $this->input_post_param[$key])) {
                return $this->input_post_param[$key];
            } else {
                return $default;
            }
        } else {
            return $default;
        }
    }
}
