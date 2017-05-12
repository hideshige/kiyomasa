<?php
/**
 * ログ モジュール
 *
 * @author   Hideshige Sawada
 * @version  1.0.3.0
 * @package  extension
 *
 */

namespace kiyomasa;

class Log
{
    public static $batch = '';

    /**
     * システム上のエラーログを記録する
     * @param string or array $log 吐き出すログ
     */
    public static function error($log) {
        $file = sprintf(
            '%slogs/%serror%s.log',
            SERVER_PATH,
            self::$batch,
            date('ymd')
        );
        self::printLog($log, $file);
    }

    /**
     * アクセス経過ログなどを記録する
     * @param string or array $log 吐き出すログ
     */
    public static function access($log) {
        $file = sprintf(
            '%slogs/%s%s.log',
            SERVER_PATH,
            self::$batch,
            date('ymd')
        );
        self::printLog($log, $file);
    }

    /*
     * 管理者用ログを記録する
     * @param string or array $log 吐き出すログ
     */
    public static function admin($log) {
        $file = sprintf(
            '%slogs/%sadmin%s.log',
            SERVER_PATH,
            self::$batch,
            date('ymd')
        );
        self::printLog($log, $file);
    }

    /**
     * ファイルにログを吐き出す
     * @param string or array $log 吐き出すログ
     * @param string $file ファイルパス
     */
    private static function printLog($log, $file) {
        if (is_array($log)) {
            ob_start();
            var_dump($log);
            $log = ob_get_clean();
        }
        $res = sprintf("%s [%s] %s\n", date('H:i:s'), IP_ADDRESS, $log);
        error_log($res, 3, $file);
    }
}
