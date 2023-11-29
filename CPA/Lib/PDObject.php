<?php

namespace CPA\Lib;

use CPA\Models\Logger\FileLog;
use Exception;
use PDO;
use PDOException;
use PDOStatement;

class PDObject {

    public $write_debug_file = true;
    private $force_debug = false;
    private $pdo_version = "2.1";
    private $pdo_db_type = "mysql";
    private $error_loc = "", $error_message = "", $error_code = 0;
    public $pdo_handle = null;
    public $affected_row = 0;
    private $pdo_host = "", $pdo_id = "", $pdo_pass = "", $pdo_dbname = "", $pdo_port = 3306;
    private $pdo_fetch_type = PDO::FETCH_ASSOC;
    private $pdo_fetch_type_list = array("assoc" => PDO::FETCH_ASSOC, "row" => PDO::FETCH_NUM);
    private $pdo_last_result = null;
    private $pdo_bug_trace = null;
    private $lastQuery = "";

    function __construct() {
        if (count($tmp = func_get_args()) > 0) {
            $tmp = $tmp[0];
            if (!$this->var_req_check(array("ip", "uid", "pwd", "tbname"), $tmp)) {
                $this->_set_error_msg(__FUNCTION__, "Connection info required.", __LINE__);
                return false;
            }
            $this->pdo_host = $tmp["ip"];
            $this->pdo_id = $tmp["uid"];
            $this->pdo_pass = $tmp["pwd"];
            $this->pdo_dbname = $tmp["tbname"];
            $this->pdo_port = isset($tmp["port"]) ? $tmp["port"] : 3306;
        }
    }

    /**
     * 
     * @param type $host_ip
     * @param type $id
     * @param type $password
     * @param type $dbname
     * @param type $port
     */
    function setConnectionInfo($host_ip, $id, $password, $dbname, $port = 3306) {
        $this->pdo_host = $host_ip;
        $this->pdo_id = $id;
        $this->pdo_pass = $password;
        $this->pdo_dbname = $dbname;
        $this->pdo_port = $port;
    }

    function __destruct() {
        $this->disconnect();
    }

    /**
     * 改變 select 的 fetch 方法
     *
     * @param CONST $type
     */
    function setFetchType($type = PDO::FETCH_ASSOC) {
        $this->pdo_fetch_type = $type;
        return $this;
    }

    /**
     * connect to PDO
     * connect info format = (ip, uid)
     *
     * @param array $connect_info
     * @return boolean
     */
    function connect($connect_info = null) {
        if ($this->pdo_handle) {
            return $this->pdo_handle;
        }
        if ($connect_info) {
            if (!$this->var_req_check(array("ip", "uid", "pwd", "tbname"), $connect_info)) {
                $this->_set_error_msg(__FUNCTION__, "Connection info required.", __LINE__);
                return false;
            }
            $this->pdo_host = $connect_info["ip"];
            $this->pdo_id = $connect_info["uid"];
            $this->pdo_pass = $connect_info["pwd"];
            $this->pdo_dbname = $connect_info["tbname"];
            $this->pdo_port = isset($connect_info["port"]) ? $connect_info["port"] : 3306;
        }
        // $pdo_port   = isset($connect_info["port"])?$connect_info["port"]:3306;
        $dsn = "{$this->pdo_db_type}:host={$this->pdo_host};dbname={$this->pdo_dbname};port={$this->pdo_port}";
        $db_options = $this->get_conn_option($this->pdo_db_type);
        try {
            $this->lastQuery = "Connect=>" . $dsn;
            $this->pdo_handle = new PDO($dsn, $this->pdo_id, $this->pdo_pass, $db_options);
            if ($this->pdo_handle) {
                // $this->pdo_handle = $handle;
                $this->pdo_handle->setAttribute(PDO::ATTR_EMULATE_PREPARES, false); // send statement twice for preventing sql injection
                $this->pdo_handle->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                return $this->pdo_handle;
            } else {
                $this->_set_error_msg(__FUNCTION__, "PDO Conenct failed.", __LINE__);
                return false;
            }
        } catch (PDOException $e) {
            $this->DetectCatchError($e->getMessage());
            $this->_set_error_msg(__FUNCTION__, $e->getMessage(), __LINE__);
            return false;
        }
    }

    /**
     * 中斷連線 handle
     * @return void
     */
    function disconnect() {
        $this->pdo_handle = null;
        $this->pdo_last_result = null;
    }

    /**
     * 取得最後一次 query 的行數（前次執行需有 SQL_CALC_FOUND_ROWS)
     *
     * @return int
     */
    function get_last_query_row() {
        $sql = 'SELECT FOUND_ROWS() as `fr`';
        $result = $this->query($sql);
        if ($result and $result[0]['fr']) {
            return $result[0]['fr'];
        } else {
            return 0;
        }
    }

    /**
     * 開始交易機制
     *
     * @param type $link
     */
    function _start_tsc() {
        try {
            if (!$this->pdo_handle) {
                $this->connect();
            }
            $this->pdo_handle->beginTransaction();
        } catch (PDOException $tscError) {
            $this->DetectCatchError($tscError->getMessage());
            $this->_set_error_msg(__FUNCTION__, $tscError->getMessage(), __LINE__);
        }
    }

    /**
     * 結束交易機制
     *
     * @param type $link
     */
    function _end_tsc() {
        try {
            if (!$this->pdo_handle) {
                $this->connect();
            }
            $this->pdo_handle->commit();
        } catch (PDOException $tscError) {
            $this->DetectCatchError($tscError->getMessage());
            $this->_set_error_msg(__FUNCTION__, $tscError->getMessage(), __LINE__);
        }
    }

    /**
     * 轉回 rollback
     */
    function _rollback() {
        try {
            if (!$this->pdo_handle) {
                $this->connect();
            }
            $this->pdo_handle->rollBack();
        } catch (PDOException $tscError) {
            $this->DetectCatchError($tscError->getMessage());
            $this->_set_error_msg(__FUNCTION__, $tscError->getMessage(), __LINE__);
        }
    }

    /**
     * 取得影響行數
     * @return int 行數
     */
    function rowCount() {
        return $this->affected_row;
    }

    /**
     * SELECT 方法
     *
     * @param mixed $column
     * @param string $table
     * @param mixed $condition
     * @param mixed $option
     * @param string $limit
     * @param object $pdo_link 連結
     * @param bool $debug 參數
     * @return array 回傳陣列或者空值（或false）
     */
    function select($column, $table, $condition = "", $option = array(), $limit = "", $pdo_link = null, $debug = 0) {
        $this->affected_row = 0;
        if (!$this->pdo_handle) {
            $this->connect();
        }
        $sql_col = $this->parse_column($column);
        $sql_table = $this->parse_table($table);
        $sql_cond = $this->parse_condition($condition);
        $sql_extnd = $this->set_select_option($option);
        if ($sql_col == "") {
            $this->_set_error_msg(__FUNCTION__, "Column required.", __LINE__);
            return null;
        }
        if ($sql_table == "") {
            $this->_set_error_msg(__FUNCTION__, "Table required.", __LINE__);
            return null;
        }
        $sql = "SELECT {$sql_col} FROM {$sql_table} ";
        if ($sql_cond != "") {
            $sql .= " WHERE {$sql_cond}";
        }
        $sql .= " {$sql_extnd} {$limit}";
        if ($debug) {
            echo $sql;
        }
        $this->lastQuery = $sql;
        if (!$this->pdo_handle) {
            $this->DetectCatchError("DB Disconnectioned!");
            return false;
        }
        try {
            $stmt = $this->pdo_handle->prepare($sql);
            // $this->pdo_last_result = $stmt;
            if ($stmt) {
                if (is_array($condition)) {
                    $stmt->execute($condition);
                } else {
                    $stmt->execute();
                }
                $this->affected_row = $stmt->rowCount();
                $result = $stmt->fetchAll($this->pdo_fetch_type);
            } else {
                $this->_set_error_msg(__FUNCTION__, "SQL execute failed.", __LINE__);
                return null;
            }
            if (count($result) == 0) {
                return false;
            }
        } catch (PDOException $e) {
            $this->DetectCatchError($e->getMessage());
            $this->_set_error_msg(__FUNCTION__, $e->getMessage(), __LINE__);
            return false;
        }
        return $result;
    }

    /**
     * 執行單筆資料搜尋
     *
     * @param mixed $column
     * @param string $table
     * @param mixed $condition
     * @param mixed $option
     * @param string $limit
     * @param object $pdo_link
     * @param boolean $debug
     * @return string
     */
    function selectone($column, $table, $condition = "", $option = array(), $limit = "", $pdo_link = null, $debug = 0) {
        $this->affected_row = 0;
        $limit = " LIMIT 1";
        $sum = $this->select($column, $table, $condition, $option, $limit, $pdo_link, $debug);
        if ($sum and count($sum) > 0) {
            return $sum[0];
        } else {
            return "";
        }
    }

    /**
     * 插入資料
     *
     * @param string $table
     * @param mixed $column
     * @param mixed $addval
     * @param object $pdo_link
     * @param boolean $debug
     * @return boolean|int
     */
    function insert($table, $column, $addval = "", $pdo_link = null, $debug = 0) {
        $this->affected_row = 0;
        if (!$this->pdo_handle) {
            $this->connect();
        }
        /**
         * still for test
         */
        if (count(func_get_args()) == 2) {
            $addval = $column;
            $column = null;
        }
        if ($table == "" or !is_string($table)) {
            return false;
        }
        if ($column != "") {
            $sql_col = $this->parse_column($column);
        } else {
            if (is_array($addval)) {
                $col_keys = array_keys($addval);
                $sql_col = implode(",", $col_keys);
            } else {
                $this->_set_error_msg(__FUNCTION__, "Column required.", __LINE__);
                return null;
            }
        }
        // if($sql_col == "") { $this->_set_error_msg(__FUNCTION__,"Column required.",__LINE__); return null;  }
        $sql = "INSERT INTO {$table} ({$sql_col}) VALUES ";
        try {
            if (is_array($column) and is_array($addval)) {
                if (count($column) != count($addval)) {
                    $this->_set_error_msg(__FUNCTION__, "Column number not same.", __LINE__);
                    return null;
                } else {
                    // bind addval to (:v,:a,:l)
                    $bindSpace = $this->bind_insert_value($addval);
                    $sql .= " ({$bindSpace}) ";
                    if ($debug) {
                        echo $sql;
                    }
                    $this->lastQuery = $sql;
                    if (!$this->pdo_handle) {
                        $this->DetectCatchError("DB Disconnectioned!");
                        return false;
                    }
                    $stmt = $this->pdo_handle->prepare($sql);
                    if ($stmt) {
                        // $this->pdo_last_result = $stmt;
                        if ($stmt and $stmt->execute($addval)) {
                            $this->affected_row = $stmt->rowCount();
                            $suc_id = $this->pdo_handle->lastInsertId();
                            return $suc_id != 0 ? $suc_id : true;
                        } else {
                            $this->_set_error_msg(__FUNCTION__, "SQL execute failed.", __LINE__);
                            return null;
                        }
                    } else {
                        $this->_set_error_msg(__FUNCTION__, "statement set failed.", __LINE__);
                        return null;
                    }
                }
            } else if (is_array($addval)) {
                $bindSpace = $this->bind_insert_value($addval);
                $sql .= " ($bindSpace) ";
                if ($debug) {
                    echo $sql;
                }
                $this->lastQuery = $sql;
                if (!$this->pdo_handle) {
                    $this->DetectCatchError("DB Disconnectioned!");
                    return false;
                }
                $stmt = $this->pdo_handle->prepare($sql);
                if ($stmt) {
                    // $this->pdo_last_result = $stmt;
                    if ($stmt->execute($addval)) {
                        $this->affected_row = $stmt->rowCount();
                        $suc_id = $this->pdo_handle->lastInsertId();
                        return $suc_id != 0 ? $suc_id : true;
                    } else {
                        $this->_set_error_msg(__FUNCTION__, "SQL execute failed.", __LINE__);
                        return null;
                    }
                } else {
                    $this->_set_error_msg(__FUNCTION__, "statement set failed.", __LINE__);
                    return null;
                }
            } else { // is_array($addval) == true
                $sql .= " ({$addval})";
                if ($debug) {
                    echo $sql;
                }
                $this->lastQuery = $sql;
                if (!$this->pdo_handle) {
                    $this->DetectCatchError("DB Disconnectioned!");
                    return false;
                }
                $success = $this->pdo_handle->exec($sql);
                if ($success) {
                    $this->affected_row = 1;
                    $suc_id = $this->pdo_handle->lastInsertId();
                    return $suc_id != 0 ? $suc_id : true;
                } else {
                    return 0;
                }
            }
        } catch (PDOException $ppExcept) {
            $this->DetectCatchError($ppExcept->getMessage());
            $this->_set_error_msg(__FUNCTION__, $ppExcept->getMessage() . ".", __LINE__);
            return null;
        }
    }

    /**
     *
     * @param string $table
     * @param mixed $expr
     * @param mixed $condition
     * @param object $pdo_link
     * @param boolean $debug
     * @return boolean
     */
    function update($table, $expr, $condition = "", $pdo_link = null, $debug = 0) {
        $this->affected_row = 0;
        if (!$this->pdo_handle) {
            $this->connect();
        }
        try {
            if (is_array($table) or !is_string($table)) {
                // table 不能為非字串
                $this->_set_error_msg(__FUNCTION__, "`Table` format wrong.", __LINE__);
                return false;
            }
            $sql = "UPDATE `{$table}` SET";
            if (is_array($expr) and is_array($condition)) {
                $expr_str = $this->bind_update_value($expr);
                $cond_str = $this->parse_condition($condition);
                $sql .= " {$expr_str} ";
                if ($cond_str != "") {
                    $sql .= " WHERE {$cond_str}";
                }
                if ($debug) {
                    echo $sql;
                }
                $this->lastQuery = $sql;
                if (!$this->pdo_handle) {
                    $this->DetectCatchError("DB Disconnectioned!");
                    return false;
                }
                $stmt = $this->pdo_handle->prepare($sql);
                if ($stmt) {
                    // $this->pdo_last_result = $stmt;
                    $exprs = array_merge($expr, $condition);
                    $stmt->execute($exprs);
                    $this->affected_row = $stmt->rowCount();
                    return true;
                } else {
                    $this->_set_error_msg(__FUNCTION__, "statement set failed.", __LINE__);
                    return false;
                }
            } else if (is_array($expr)) {
                $expr_str = $this->bind_update_value($expr);
                $sql .= " {$expr_str} ";
                if ($condition != "") {
                    $sql .= " WHERE {$condition}";
                }
                if ($debug) {
                    echo $sql;
                }
                $this->lastQuery = $sql;
                if (!$this->pdo_handle) {
                    $this->DetectCatchError("DB Disconnectioned!");
                    return false;
                }
                $stmt = $this->pdo_handle->prepare($sql);
                if ($stmt) {
                    // $this->pdo_last_result = $stmt;
                    $stmt->execute($expr);
                    $this->affected_row = $stmt->rowCount();
                    return true;
                } else {
                    $this->_set_error_msg(__FUNCTION__, "statement set failed.", __LINE__);
                    return false;
                }
            } else if (is_array($condition)) {
                $sql .= " {$expr} ";
                $cond_str = $this->parse_condition($condition);
                if ($cond_str != "") {
                    $sql .= " WHERE {$cond_str}";
                }
                if ($debug) {
                    echo $sql;
                }
                $this->lastQuery = $sql;
                if (!$this->pdo_handle) {
                    $this->DetectCatchError("DB Disconnectioned!");
                    return false;
                }
                $stmt = $this->pdo_handle->prepare($sql);
                if ($stmt) {
                    // $this->pdo_last_result = $stmt;
                    $stmt->execute($condition);
                    $this->affected_row = $stmt->rowCount();
                    return true;
                } else {
                    $this->_set_error_msg(__FUNCTION__, "statement set failed.", __LINE__);
                    return false;
                }
            } else {
                $sql .= " {$expr} ";
                if ($condition != "") {
                    $sql .= " WHERE {$condition}";
                }
                if ($debug) {
                    echo $sql . "<br>\n";
                }
                $this->lastQuery = $sql;
                // $this->pdo_handle->exec($sql);
                $stmt = $this->pdo_handle->prepare($sql);
                if ($stmt) {
                    $stmt->execute();
                    $this->affected_row = $stmt->rowCount();
                    return true;
                } else {
                    return false;
                }
            }
        } catch (PDOException $ppExcept) {
            $this->DetectCatchError($ppExcept->getMessage());
            $this->_set_error_msg(__FUNCTION__, $ppExcept->getMessage() . ".", __LINE__);
            return false;
        }
    }

    /**
     * 刪除資料
     *
     * @param string $table
     * @param mixed $condition
     * @param object $pdo_link
     * @return boolean|int
     */
    function delete($table, $condition) {
        $this->affected_row = 0;
        if (!$this->pdo_handle) {
            $this->connect();
        }
        try {
            if (is_array($table) or !is_string($table)) {
                // table 不能為非字串
                $this->_set_error_msg(__FUNCTION__, "`Table` format wrong.", __LINE__);
                return false;
            }
            $sql = "DELETE FROM `{$table}` ";
            if (is_array($condition)) {
                $cond_str = $this->parse_condition($condition);
                if ($cond_str != "") {
                    $sql .= " WHERE {$cond_str}";
                }
                $this->lastQuery = $sql;
                if (!$this->pdo_handle) {
                    $this->DetectCatchError("DB Disconnectioned!");
                    return false;
                }
                $stmt = $this->pdo_handle->prepare($sql);
                if ($stmt) {
                    // $this->pdo_last_result = $stmt;
                    if ($stmt->execute($condition)) {
                        $this->affected_row = $stmt->rowCount();
                        return true;
                    } else {
                        $this->_set_error_msg(__FUNCTION__, "SQL execute failed.", __LINE__);
                        return null;
                    }
                } else {
                    $this->_set_error_msg(__FUNCTION__, "statement set failed.", __LINE__);
                    return null;
                }
            } else {
                if ($condition != "") {
                    $sql .= " WHERE {$condition}";
                }
                $this->lastQuery = $sql;
                if (!$this->pdo_handle) {
                    $this->DetectCatchError("DB Disconnectioned!");
                    return false;
                }
                if ($this->affected_row = $this->pdo_handle->exec($sql)) {
                    return true;
                } else {
                    $this->_set_error_msg(__FUNCTION__, "SQL execute failed.", __LINE__);
                    return false;
                }
            }
        } catch (PDOException $pdoException) {
            $this->DetectCatchError($pdoException->getMessage());
            $this->_set_error_msg(__FUNCTION__, $pdoException->getMessage() . ".", __LINE__);
            return null;
        }
    }

    /**
     * 發現的列表數
     *
     * @return int
     */
    function found_row() {
        // 在 select 可能會有問題 （資料庫本身不支援）
        if ($this->pdo_last_result) {
            $count = $this->pdo_last_result->rowCount();
            unset($this->pdo_last_result);
            return $count;
        } else {
            return 0;
        }
    }

    /**
     * parse column data
     * if[array](a,b,c)  return -> 'a,b,c'
     * if[string]'a,b,c' return -> 'a,b,c'
     * else              return -> '' (sql will error)
     */
    private function parse_column($columns) {
        $sql_column = "";
        if (is_array($columns)) {
            $sql_column = implode(",", $columns);
        } else if (is_string($columns)) {
            $sql_column = $columns;
        } else {
            $sql_column = "";
        }
        return $sql_column;
    }

    /**
     * parse tables
     * if[array](a,b,c) return -> 'a,b,c'
     * if[string]'a,b,c' return -> 'a,b,c'
     * else              return -> '' (sql will error)
     */
    private function parse_table($tables) {
        $sql_table = "";
        if (is_array($tables)) {
            $sql_table = implode(",", $tables);
        } else if (is_string($tables)) {
            $sql_table = $tables;
        } else {
            $sql_table = "";
        }
        return $sql_table;
    }

    private function parse_condition(&$condition) {
        $sql_cond = "";
        if (is_array($condition)) {
            $dupCond = $condition;
            foreach ($condition as $cond_col => $cond) {
                if ($cond === "") {
                    unset($dupCond[$cond_col]);
                    continue;
                }
                $sql_cond .= " `{$cond_col}` = :{$cond_col} and";
                unset($dupCond[$cond_col]);
                $dupCond[":{$cond_col}"] = trim($cond);
            }
            $condition = $dupCond;
            $sql_cond = preg_replace("/and$/", "", $sql_cond);
        } else if (is_string($condition)) {
            $sql_cond = $condition;
        } else {
            $sql_cond = "";
        }
        return $sql_cond;
    }

    /**
     * parse value
     * if[array](a,b,c)  return -> 'a,b,c'
     * if[string]'a,b,c' return -> 'a,b,c'
     * else              return -> '' (sql will error)
     */
    private function parse_value($value) {
        $sql_val = "";
        if (is_array($value)) {
            $sql_val = implode(",", $value);
        } else if (is_string($value)) {
            $sql_value = $value;
        } else {
            $sql_value = "";
        }
        return $sql_val;
    }

    private function set_select_option($options) {
        $opt_str = '';
        if (is_array($options)) {
            foreach ($options as $optc => $opt) {
                switch ($optc) {
                    case "order":
                        $opt_str .= " {$optc} by {$opt}";
                        break;
                    case "group":
                        $opt_str .= " {$optc} by {$opt}";
                        break;
                }
            }
        } else {
            $opt_str = $options;
        }
        return $opt_str;
    }

    /**
     *  <summary> Create bind query for insert column. </summary>
     *  if value is array like array("me","you")
     *  here will convert it for pdo prepare statement format as
     *  array(":0"=>"me", ":1"=>"you") and also create binded string (:0,:1)
     */
    private function bind_insert_value(&$added_value) {
        if (!is_array($added_value)) {
            return "";
        }
        $bindSpace = "";
        $dupValue = $added_value;
        foreach ($added_value as $key => $dv) {
            $bindSpace .= ":{$key},";
            $dupValue[":{$key}"] = $dv;
            unset($dupValue[$key]);
        }
        $added_value = $dupValue;
        $bindSpace = rtrim($bindSpace, ","); // ---> ?,?,? ...etc
        return $bindSpace;
    }

    private function bind_update_value(&$upded_value) {
        if (!is_array($upded_value)) {
            return "";
        }
        $dupValue = $upded_value;
        $bindSpace = "";
        foreach ($upded_value as $key => $dv) {
            $bindSpace .= "`{$key}` = :{$key},";
            $dupValue[":{$key}"] = $dv;
            unset($dupValue[$key]);
        }
        $upded_value = $dupValue;
        $bindSpace = rtrim($bindSpace, ","); // ---> ?,?,? ...etc
        return $bindSpace;
    }

    /**
     * check required variable
     * >>> req = required
     * >>> comp = data input
     */
    private function var_req_check($req = array(), $comp = "") {
        if ($comp == null or count((array) $comp) <= 0) {
            return false;
        }
        foreach ($req as $reqvar) {
            if (!isset($comp[$reqvar])) {
                return false;
            }
        }
        return true;
    }

    // defaul connection options
    // ref:
    private function get_conn_option($type) {
        switch ($type) {
            case "mysql":
                $option = array(
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
                );
                return $option;
            default:
                return array();
        }
    }

    private function _set_error_msg($loc = "", $msg = "", $code = "") {
        $this->error_loc = $loc;
        $this->error_message = $msg;
        $this->error_code = $code;
    }

    function _throw_error() {
        if ($this->error_message != "") {
            echo "Error: {$this->error_loc} (L:{$this->error_code}): {$this->error_message}";
            if ($this->pdo_handle) {
                echo " (PDO Message [errorCode: " . $this->pdo_handle->errorCode() . "]: " . var_export($this->pdo_handle->errorInfo(), true) . ")";
            }
        }
        $this->error_message = "";
        $this->error_code = 0;
        $this->error_loc = "";
        die();
    }

    function _return_error() {
        $error_log = "";
        if ($this->error_message != "") {
            $error_log .= "Error: {$this->error_loc} (L:{$this->error_code}): {$this->error_message}\n";
            if ($this->pdo_handle) {
                $error_log .= " (PDO Message [errorCode: " . $this->pdo_handle->errorCode() . "]: " . var_export($this->pdo_handle->errorInfo(), true) . ")\n";
            }
        }
        $error_log .= "\n>>>>>>\n";
        return $error_log;
    }

    function query($query) {
        return $this->do_query($query);
    }

    function do_query($query) {
        $this->affected_row = 0;
        if (!$this->pdo_handle) {
            $this->connect();
        }
        try {
            $this->lastQuery = $query;
            if (!$this->pdo_handle) {
                $this->DetectCatchError("DB Disconnectioned!");
                return false;
            }
            $stmt = $this->pdo_handle->prepare($query);
            if ($stmt) {
                // $this->pdo_last_result = $stmt;
                $exeret = $stmt->execute();
                if ($exeret) {
                    if (preg_match("/^(select|show)/i", trim($query))) {
                        $arr = $stmt->fetchAll($this->pdo_fetch_type);
                        if ($arr) {
                            $this->affected_row = $stmt->rowCount();
                            return $arr;
                        } else {
                            $this->_set_error_msg(__FUNCTION__, "SQL execute failed.", __LINE__);
                            return false;
                        }
                    } else if (preg_match("/^insert/i", trim($query))) {
                        $lastId = $this->pdo_handle->lastInsertId();
                        $this->affected_row = $stmt->rowCount();
                        return $lastId;
                    } else {
                        $this->affected_row = $stmt->rowCount();
                        return true;
                    }
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } catch (Exception $ex) {
            $this->DetectCatchError($ex->getMessage());
            return false;
        }
    }

    /**
     * bind param into query
     * 
     * @param  string $query     [description]
     * @param  array $bind_data [description]
     * @return mixed            [description]
     */
    function do_bind_query($query, $bind_data) {
        $this->affected_row = 0;
        if (!$this->pdo_handle) {
            $this->connect();
        }
        try {
            $query = trim($query);
            $this->lastQuery = $query;
            $stmt = $this->pdo_handle->prepare($query);
            $real_bind_data = array();
            foreach ($bind_data as $key => $value) {
                if (strpos($key, ":") === false) {
                    $key = ":" . $key;
                }
                $real_bind_data[$key] = $value;
            }
        } catch (Exception $err) {
            $this->DetectCatchError($err->getMessage());
            return false;
        }
        try {
            $this->lastQuery = $query;
            $stmt->execute($real_bind_data);
            if ($stmt->rowCount() > 0) {
                if (preg_match("/^(select|show)/i", $query)) {
                    $arr = $stmt->fetchAll($this->pdo_fetch_type);
                    if ($arr) {
                        $this->affected_row = $stmt->rowCount();
                        return $arr;
                    } else {
                        $this->_set_error_msg(__FUNCTION__, "SQL execute failed.", __LINE__);
                        return false;
                    }
                } else if (preg_match("/^insert/i", $query)) {
                    $lastId = $this->pdo_handle->lastInsertId();
                    $this->affected_row = $stmt->rowCount();
                    return $lastId;
                } else {
                    $this->affected_row = $stmt->rowCount();
                    return true;
                }
            } else {
                return false;
            }
        } catch (PDOException $ex) {
            $this->DetectCatchError($ex->getMessage());
            return false;
        } catch (Exception $ex) {
            $this->DetectCatchError($ex->getMessage());
            return false;
        }
    }

    /**
     * alias of do_bind_query ...
     * @param  [type] $query     [description]
     * @param  [type] $bind_data [description]
     * @return mixed            [description]
     */
    function bind_query($query, $bind_data = array()) {
        return $this->do_bind_query($query, $bind_data);
    }

    function DetectCatchError($err = "") {
        if ($this->write_debug_file) {
            $msg = $this->lastQuery . "\n[ERR]" . $err . "\n";
            FileLog::WriteTextLog('PDO_DB_ERROR', $msg);
            FileLog::WriteSysLog('PDO_DB_ERROR', $msg);
        }
        if ($this->force_debug) {
            echo "<pre>";
            debug_print_backtrace();
            echo "</pre>";
        }
    }

    /**
     * Bind query and return statement, then put statement into query_stmt()
     * @param  string $query
     * @return PDOStatement
     */
    function bind_stmt($query) {
        if (!$this->pdo_handle) {
            $this->connect();
        }
        $stmt = $this->pdo_handle->prepare($query);
        if ($stmt) {
            return $stmt;
        } else {
            return false;
        }
    }

    /**
     * execute pdo statement with param
     * @param  PDOStatement $stmt
     * @param  mixed $bind_data
     * @return mixed
     */
    function query_stmt($stmt, $bind_data = array()) {
        if (!$this->pdo_handle) {
            $this->connect();
        }
        $this->affected_row = 0;
        $real_bind_data = array();
        foreach ($bind_data as $key => $value) {
            if (strpos($key, ":") === false) {
                $key = ":" . $key;
            }
            $real_bind_data[$key] = $value;
        }
        try {
            $stmt->execute($real_bind_data);
            if ($stmt->rowCount() > 0) {
                if (preg_match("/^(select|show)/i", $stmt->queryString)) {
                    return $stmt->fetchAll($this->pdo_fetch_type);
                } else if (preg_match("/^insert/i", $stmt->queryString)) {
                    $lastId = $this->pdo_handle->lastInsertId();
                    $this->affected_row = $stmt->rowCount();
                    return $lastId;
                } else {
                    $this->affected_row = $stmt->rowCount();
                    return true;
                }
            } else {
                return false;
            }
        } catch (PDOException $ex) {
            $this->DetectCatchError($ex->getMessage());
            return false;
        } catch (Exception $ex) {
            $this->DetectCatchError($ex->getMessage());
            return false;
        }
    }
}
