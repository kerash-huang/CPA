<?php

namespace CPA\Lib\SystemLog;

class SysLogger {
    public $_facility; // 0-23
    public $_severity; // 0-7
    public $_hostname; // no embedded space, no domain name, only a-z A-Z 0-9 and other authorized characters
    public $_fqdn;
    public $_ip_from;
    public $_process;
    public $_content;
    public $_msg;
    public $_server;   // Syslog destination server
    public $_port;     // Standard syslog port is 514
    public $_timeout;  // Timeout of the UDP connection (in seconds)

    public function __construct($facility = 16, $severity = 5, $hostname = "", $process = "", $content = "") {
        $this->_msg = '';
        $this->_server = '127.0.0.1';
        $this->_port = 514;
        $this->_timeout = 10;

        $this->_facility = $facility;

        $this->_severity = $severity;

        $this->_hostname = $hostname;
        if ($this->_hostname == "") {
            if (isset($_ENV["COMPUTERNAME"])) {
                $this->_hostname = $_ENV["COMPUTERNAME"];
            } elseif (isset($_ENV["HOSTNAME"])) {
                $this->_hostname = $_ENV["HOSTNAME"];
            } else {
                $this->_hostname = "WEBSERVER";
            }
        }
        $this->_hostname = substr($this->_hostname, 0, strpos($this->_hostname . ".", "."));



        if (isset($_SERVER["SERVER_ADDR"])) {
            $this->_ip_from = $_SERVER["SERVER_ADDR"];
        } else {
            $this->_ip_from = '127.0.0.1';
        }


        $this->_process = $process;
        if ($this->_process == "") {
            $this->_process = "PHP";
        }

        $this->_content = $content;
        if ($this->_content == "") {
            $this->_content = "PHP generated message";
        }
    }

    /**
     * Undocumented function
     *
     * @param string $facility
     * @return void
     */
    function SetFacility($facility) {
        $this->_facility = $facility;
    }

    function SetSeverity($severity) {
        $this->_severity = $severity;
    }

    function SetHostname($hostname) {
        $this->_hostname = $hostname;
    }

    function SetFqdn($fqdn) {
        $this->_fqdn = $fqdn;
    }

    function SetIpFrom($ip_from) {
        $this->_ip_from = $ip_from;
    }

    function SetProcess($process) {
        $this->_process = $process;
    }

    function SetContent($content) {
        $this->_content = $content;
    }

    function SetMsg($msg) {
        $this->_msg = $msg;
    }

    function SetServer($server) {
        $this->_server = $server;
    }

    function SetPort($port) {
        if ((intval($port) > 0) && (intval($port) < 65536)) {
            $this->_port = intval($port);
        }
    }

    function SetTimeout($timeout) {
        if (intval($timeout) > 0) {
            $this->_timeout = intval($timeout);
        }
    }

    function Send($server = "", $content = "", $file_name = "", $timeout = 0) {
        if ($server != "") {
            $this->_server = $server;
        }

        if ($content != "") {
            $this->_content = $content;
        }

        if (intval($timeout) > 0) {
            $this->_timeout = intval($timeout);
        }

        if ($this->_facility < 0) {
            $this->_facility = 0;
        }
        if ($this->_facility > 23) {
            $this->_facility = 23;
        }
        if ($this->_severity < 0) {
            $this->_severity = 0;
        }
        if ($this->_severity > 7) {
            $this->_severity = 7;
        }

        $this->_process = substr($this->_process, 0, 32);

        $actualtime = time();
        $month = date("M", $actualtime);
        $day = substr("  " . date("j", $actualtime), -2);
        $hhmmss = date("H:i:s", $actualtime);
        $timestamp = $month . " " . $day . " " . $hhmmss;

        /**
         *   rsyslog  固定吃的格式
         * {timestamp} {$file_name} {server_ip} {servername} {content}
         *
         *  Dec 16 17:50:29 tony_secret_files_20191216 172.87.0.87 UFA_FRONT {content}
         *   存放的LOG = http://{syslog主機}/{some_path}/{$file_name}.txt
         * */
        $pri = "<" . ($this->_facility * 8 + $this->_severity) . ">";
        $header = $timestamp . " " . $file_name . " " . $this->_ip_from . " " . $this->_hostname;
        $msg = $this->_content;


        $message = substr($pri . $header . " " . $msg, 0, 4096);

        $fp = fsockopen("udp://" . $this->_server, $this->_port, $errno, $errstr);
        if ($fp) {
            fwrite($fp, $message);
            fclose($fp);
            $result = $message;
        } else {
            $result = "ERROR: $errno - $errstr";
        }
        return $result;
    }
}
