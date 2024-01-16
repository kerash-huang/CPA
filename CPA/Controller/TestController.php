<?php

namespace CPA\Controller;

use CPA\API;
use CPA\Models\DbGame;
use CPA\Models\Input;
use CPA\Models\Logger\FileLog;
use CPAGlobal;

class TestController extends APIBase {

    /**
     *
     * @var CPA\API;
     */
    private $cpapi;

    function __construct(API $base) {
        parent::__construct($base);
        $this->cpapi = $base;
    }

    public function WriteFileTest() {
        FileLog::WriteTextLog('WRITE_FILE_TEST', 'test file __' . CPAGlobal::$now_timestamp);
        FileLog::WriteSysLog('WRITE_SYS_TEST', 'test file __' . CPAGlobal::$now_timestamp);
        return true;
    }

    public function TestEcho() {
        echo "TEST MSG";
        die();
    }

    public function TestDB() {
        var_dump(CPAGlobal::$db);
        die();
    }

}
