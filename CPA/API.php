<?php

namespace CPA;

use CPA\Core\Base;
use CPA\Core\Response;
use CPA\Lib\PDObject;
use CPA\Lib\APIMonitor;
use CPAConfig;
use Exception;

class API extends Base {

    public $controller;
    public $action;
    public $cpa_db;
    public $cpa_slave_db;
    public $monitor_job = array();
    public $monitor_action = array();


    function __construct() {
        $this->cpa_db = new PDObject(array("ip" => CPAConfig::$CPA_DB_HOST, "uid" => CPAConfig::$CPA_DB_USER, "pwd" => CPAConfig::$CPA_DB_PASS, "tbname" => CPAConfig::$CPA_DB_TABLE));
        if (isset(CPAConfig::$CPA_SLAVE_DB_HOST)) {
            $this->cpa_slave_db = new PDObject(array("ip" => CPAConfig::$CPA_SLAVE_DB_HOST, "uid" => CPAConfig::$CPA_DB_USER, "pwd" => CPAConfig::$CPA_DB_PASS, "tbname" => CPAConfig::$CPA_DB_TABLE));
        } else {
            $this->cpa_slave_db = $this->cpa_db;
        }
        APIMonitor::start_up($this->cpa_db, $this->cpa_slave_db);
        $this->cpa_db->disconnect();

        parent::__construct();
    }

    /**
     * 呼叫功能
     * @param string $controller
     * @param string $action
     */
    public function go($controller, $action) {
        if (!CPAConfig::$MonitorMethod or (!in_array($controller, $this->monitor_job) or !in_array($action, $this->monitor_action))) {
            APIMonitor::no_monitor();
        }
        $this->controller = $controller;
        $this->action = $action;
        $resp = new Response();
        APIMonitor::StartUpLog($controller, $action);
        $this->routing->check($this->request_method, $controller, $action);
        $controller_name = __NAMESPACE__ . "\\Controller\\" . $controller . "Controller";
        $my_controller = new $controller_name($this);
        if (method_exists($my_controller, $action)) {
            try {
                $response = $my_controller->$action();
                if (!is_string($response)) {
                    $resp->Success('', $response)->Show();
                } else if ($response === null) {
                    $resp->Fail(404, "Api data return fail ({$controller}/{$action})")->Show();
                } else {
                    $resp->Success('', $response)->Show();
                }
            } catch (Exception $e) {
                $resp->Fail($e->getCode(), $e->getMessage())->Show();
            }
        } else {
            $resp->Fail(500, "Method '{$action}' not be defined.")->Show();
        }
    }

    public function getMethods($controller) {
        $controller_name = __NAMESPACE__ . "\\Controller\\" . $controller . "Controller";
        if (class_exists($controller_name)) {
            $my_controller = new $controller_name($this);
            $reflection = new \ReflectionClass($my_controller);

            $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
            $method_list = array();
            foreach ($methods as $mth) {
                if ($reflection->getName() == $mth->class) {
                    if ($mth->getName() === '__construct') {
                        continue;
                    }
                    $method_list[] = $mth->getName();
                }
            }
            return $method_list;
        } else {
            return null;
        }
    }

    function __call($method, $args) {
        if (in_array($method, array("get", "post", "put"))) {
            if ($this->api_param_debug_mode) {
                $method = "get";
            }
            $this->routing->add_route($method, $args[0], $args[1]);
        } else {
            throw new Exception("System Error ({$method})", 500);
        }
    }
}
