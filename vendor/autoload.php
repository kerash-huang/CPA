<?php

function CPALoader($className) {
    $class_file_folder_base = __DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR;
    $class_location = $class_file_folder_base . str_replace("\\", DIRECTORY_SEPARATOR, $className) . ".php";
    if (file_exists($class_location)) {
        require_once $class_location;
        return true;
    } else {
        if (CPAConfig::$DebugMode or CPAGlobal::$itdebug) {
            throw new \Exception("try to find {$className} but " . $class_location . " not found.");
        }
//        throw new \Exception("System Error.", 500);
        return false;
    }
}

spl_autoload_register("CPALoader");
