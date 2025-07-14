<?php
/**
 * ログ モジュール
 *
 * @author   Sawada Hideshige
 * @version  1.0.7.0
 * @package  device
 * 
 * メモ: Linuxのログローテーションは/etc/logrotate.dで設定できる
 *
 */

namespace Php\Framework\Device;

class Log
{
    public static string $add_name = '';
    public static string $mark = '';

    /**
     * システム上のエラーログを記録する
     * @param string|array $log 吐き出すログ
     * @return void
     */
    public static function error($log): void
    {
        $file = sprintf(
            '%s%serror.log',
            LOG_PATH,
            self::$add_name
        );
        self::printLog($log, $file);
    }

    /**
     * アクセス経過ログなどを記録する
     * @param string|array $log 吐き出すログ
     * @return void
     */
    public static function access($log): void
    {
        $file = sprintf(
            '%s%saccess.log',
            LOG_PATH,
            self::$add_name
        );
        self::printLog($log, $file);
    }

    /**
     * ファイルにログを吐き出す
     * @param string|array $log 吐き出すログ
     * @param string $file ファイルパス
     * @return void
     */
    private static function printLog(string|array $log, string $file): void
    {
        if (is_array($log)) {
            ob_start();
            var_dump($log);
            $log = ob_get_clean();
        }
        $res = sprintf(
            "%s [%s] %s %s\n",
            date('Y-m-d H:i:s'),
            IP_ADDRESS,
            $log,
            self::$mark
        );
        error_log($res, 3, $file);
    }
}
