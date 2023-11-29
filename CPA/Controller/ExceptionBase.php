<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace CPA\Controller;

use Exception;

class ExceptionBase {

    /**
     * 
     * @param type $key_name
     * @throws Exception
     */
    function errParamLoss($key_name) {
        throw new Exception("缺少参数 '{$key_name}' ", 1101);
    }

    /**
     * 
     * @param type $key_name
     * @throws Exception
     */
    function errParamValue($key_name) {
        throw new Exception("参数不正确 '{$key_name}' ", 1102);
    }

    /**
     * 资料格式错误
     * @throws Exception
     */
    function errDataType($n = '') {
        throw new Exception("参数类型错误." . ($n ? "({$n})" : ""), 1112);
    }

    /**
     * 资料长度错误
     * @throws Exception
     */
    function errDataLength($n = '') {
        throw new Exception("资料长度错误." . ($n ? "({$n})" : ""), 1113);
    }

    /**
     * 只接受英数文字
     * @param string $name
     * @throws Exception
     */
    function errCharOnly($name = '') {
        throw new Exception("{$name} 仅接受英数字符.", 1115);
    }

    /**
     * 只接受数字
     * @throws Exception
     */
    function errDigitOnly() {
        throw new Exception("仅接受数字 (0-9) 字符.", 1116);
    }

    /**
     * 禁止跨月
     * @throws Exception
     */
    function errCrossMonth() {
        throw new Exception("Date range error. Can't cross month.", 1201);
    }

    /**
     * 查无用户
     * @throws Exception
     */
    function errUserNotFound() {
        throw new Exception("用户不存在", 2101);
    }

    /**
     * 查无用户
     * @throws Exception
     */
    function errUserNotAvailable() {
        throw new Exception("帐号冻结中，无法开启游戏", 2103);
    }

    /**
     * 查无用户
     * @throws Exception
     */
    function errDataNotFound() {
        throw new Exception("查无资料", 2102);
    }

    /**
     * 
     * @throws Exception
     */
    function errDataNotMatch() {
        throw new Exception("资料不符", 2104);
    }

    /**
     * 资料建立失败
     * @throws Exception
     */
    function errDataCreateFail() {
        throw new Exception("资料建立失败", 2105);
    }

    /**
     * 金额需 > 0
     * @throws Exception
     */
    function errLTZero($name) {
        throw new Exception("{$name} 数值需大于 0 ", 2106);
    }

    /**
     * Token 错误
     * @throws Exception
     */
    function errTokenNotFound() {
        throw new Exception("失败，请重新登入.", 2110);
    }

    /**
     * Token 过期
     * @throws Exception
     */
    function errTokenExpired() {
        throw new Exception("失败，请重新登入.", 2111);
    }

    /**
     * 密码错误
     * @throws Exception
     */
    function errPassword() {
        throw new Exception("当前密码错误", 2112);
    }

    /**
     * 当前提款密码错误
     * @throws Exception
     */
    function errWithdrawPassword() {
        throw new Exception("当前提款密码错误", 2113);
    }

    /**
     * 资料验证无效（还没验证过） 
     * @throws Exception
     */
    function errProfileValid() {
        throw new Exception("个人资料尚未验证", 2114);
    }

    /**
     * 资料验证无效（还没验证过） 
     * @throws Exception
     */
    function errProfileInvalid() {
        throw new Exception("个人资料错误", 2120);
    }

    /**
     * 输入与旧密码不同的密码
     * @throws Exception
     */
    function errRenewPassword() {
        throw new Exception("请使用与旧密码不同之新密码.", 2115);
    }

    /**
     * 游戏 API 档案不存在
     * @throws Exception
     */
    function errGameFileNotFound($game = '') {
        throw new Exception("未设定游戏模组资料." . ($game ? '(' . $game . ')' : ''), 3029);
    }

    /**
     * 游戏 API 档案不存在
     * @throws Exception
     */
    function errGameNotFound() {
        throw new Exception("游戏资料错误.", 3030);
    }

    /**
     * 
     * @throws Exception
     */
    function errNoDataUpdate() {
        throw new Exception("资料未更新.", 3031);
    }

    /**
     * 游戏未开放
     * @throws Exception
     */
    function errGameNotAvailable($msg) {
        throw new Exception("目前无法开启游戏，{$msg}.", 3032);
    }

    /**
     * 游戏帐号不存在
     * @throws Exception
     */
    function errGameMemberNotFound() {
        throw new Exception("玩家资料错误.", 3033);
    }

    /**
     * 提款锁定
     * @throws Exception
     */
    function errUserWithdrawBlocked() {
        throw new Exception("账户提款暂时关闭中", 3034);
    }

    /**
     * 系统更新失败
     * @throws Exception
     */
    function errSystemUpdateFail() {
        throw new Exception("Data update fail, please retry.", 9001);
    }

    /**
     * 一般错误讯息
     * @param type $msg
     * @param int $errCode
     * @throws Exception
     */
    function errMsgText($msg, $errCode = 9999) {
        throw new Exception($msg, $errCode);
    }

    /**
     * 资料新增错误
     * @throws Exception
     */
    function errDataInsertFail() {
        throw new Exception("Data added fail. please retry", 9010);
    }

    /**
     * 查无订单资料
     */
    function errOrderNotFound($order_no) {
        throw new Exception("查无单号 ({$order_no}) 纪录.", 4404);
    }

    /**
     * 查无订单资料
     */
    function errRakeOrderNotFound($order_no) {
        throw new Exception("查无返水纪录.", 4406);
    }

    /**
     * 订单已锁定
     */
    function errOrderLocked($order_no) {
        throw new Exception("订单 ({$order_no}) 处理状态已锁定.", 4407);
    }

    /**
     * 查无奖金资料
     */
    function errPrizeNotFound() {
        throw new Exception("查无奖金纪录.", 4405);
    }

    /**
     * 奖金已锁定
     * @throws Exception
     */
    function errBonusLocked() {
        throw new Exception("会员奖金功能已锁定.", 5812);
    }

    /**
     * 
     */
    function errFailRetry($message = '') {
        throw new Exception("失败，请重新尝试." . ($message ? "({$message})" : ""), 9012);
    }


    /**
     * 查无订单资料
     */
    function errRetryLater() {
        throw new Exception("失败，请稍后重新尝试.", 9015);
    }

    /**
     * 信件发送频繁
     */
    function errMsgTooOften() {
        throw new Exception("讯息发送过快，请稍待后重新尝试!", 9013);
    }

    /**
     * 锁定登入
     * @param type $text
     * @param type $reason
     * @throws Exception
     */
    function errLockLogin($text, $reason = '') {
        throw new Exception($text . ($reason ? "({$reason})" : ""), 33333);
    }

    /**
     * 
     * @param type $data
     * @throws Exception
     */
    function errAuthFail($data) {
        throw new Exception("验证失败 ({$data})!", 9018);
    }

    /**
     * 转账功能被关闭的状态
     * @throws Exception
     */
    function errLockGameTransaction() {
        throw new Exception('转帐功能关闭中', 6001);
    }

    private $backup_code_match = array(
        5999 => '提款密码不得与登入密码重复',
        6666 => '游戏代码不存在',
        8701 => '无法申请返水',
        8702 => 'NoCondition',
        8703 => 'NoCalcRecord',
        8704 => 'BetNotEnough',
        8705 => 'RecvErrorRetry',
        8706 => 'SendErrorRetry',
        8707 => 'ReachMaxAmount',
        8708 => 'WaitForDailyCalc',
        9090 => '登入限制',
        9701 => '简讯系统失败',
        9995 => '失败',
    );
}
