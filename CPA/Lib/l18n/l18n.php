<?php

namespace CPA\Lib\l18n;

class l18n {

    var $_LANG_TRANS_DATA = array();
    var $_CURRENT_LANG = "cn";
    var $IsURLEncode = true;
    var $_SYSTEM_LANG = "cn";

    function __construct($lang = "cn") {
        $this->_CURRENT_LANG = $lang;
        $this->LoadLangFile($this->_CURRENT_LANG);
    }

    function LoadLangFile($lang = "cn") {
        $filePath = __DIR__ . DIRECTORY_SEPARATOR . strtoupper($lang) . ".lang.json";
        if (file_exists($filePath)) {
            $LangContent = file_get_contents($filePath);
            $this->_LANG_TRANS_DATA = json_decode($LangContent, true);
        } else {
            $filePath = __DIR__ . DIRECTORY_SEPARATOR . strtoupper($this->_SYSTEM_LANG) . ".lang.json";
            $LangContent = file_get_contents($filePath);
            $this->_LANG_TRANS_DATA = json_decode($LangContent, true);
        }
    }

    function SetURLEnc($bool = true) {
        $this->IsURLEncode = $bool;
        return $this;
    }

    function Get($Key) {
        $args = func_get_args();
        if (!is_array($args)) {
            $args = array($args);
        }
        if (count($args) == 1) {
            if (isset($this->_LANG_TRANS_DATA[$Key])) {

                return $this->IsURLEncode ? urlencode($this->_LANG_TRANS_DATA[$Key]) : $this->_LANG_TRANS_DATA[$Key];
            } else {
                return $Key;
            }
        } else {
            // >1
            unset($args[0]);
            if (!isset($this->_LANG_TRANS_DATA[$Key])) {
                return $Key;
            }
            $bLangTxt = $this->_LANG_TRANS_DATA[$Key];
            foreach ($args as $pos => $repl) {
                $bLangTxt = str_replace("{{$pos}}", $repl, $bLangTxt);
            }
            return $this->IsURLEncode ? urlencode($bLangTxt) : $bLangTxt;
        }
    }

}
