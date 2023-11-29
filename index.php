<?php

// v1.4.3
use CPA\API;
use CPA\Core\Response;

require_once __DIR__ . "/CPAConfig.php";
require_once __DIR__ . "/vendor/autoload.php";

try {
    error_reporting(E_ALL);
    ini_set('display_errors', true);
    CPAGlobal::_init();
    $method = \CPA\Lib\Input::Get("method");
    $controller_action = explode("/", $method);
    foreach ($controller_action as $pos => $value) {
        if (trim($value) == "") {
            unset($controller_action[$pos]);
        }
    }
    if (count($controller_action) >= 2) {
        $controller = array_shift($controller_action);
        $action = array_shift($controller_action);
    } else {
        throw new \Exception("Action not allowed.");
    }
    $casino_public_api = new API();
    // 限定支援的功能，免得被呼叫到不該有的功能
    // 任何功能只要不在這邊設定都會無效
    require_once __DIR__ . '/CustomRouting.php';
    // 執行 api
    $casino_public_api->go($controller, $action);
} catch (Exception $ex) {
    $resp = new Response();
    $resp->Fail($ex->getCode(), $ex->getMessage())->Show();
}
