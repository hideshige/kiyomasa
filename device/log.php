<?php
/**
 * ログ モジュール
 *
 * @author   Sawada Hideshige
 * @version  1.0.8.0
 * @package  device
 * 
 * メモ: Linuxのログローテーションは/etc/logrotate.dで設定できる
 *
 */

namespace Php\Framework\Device;

class Log
{
    public static string $name = 'system.log';
    public static string $add_name = '';
    public static string $mark = '';

    /**
     * システム上のエラーログを記録する
     * @param string|array $log 吐き出すログ
     * @return void
     */
    public static function error(string|array $log): void
    {
        self::$name = 'error.log';
        self::printLog($log);
    }

    /**
     * アクセス経過ログなどを記録する
     * @param string|array $log 吐き出すログ
     * @return void
     */
    public static function access(string|array $log): void
    {
        self::$name = 'access.log';
        self::printLog($log);
    }

    /**
     * ログを記録する
     * @param string|array $log 吐き出すログ
     * @return void
     */
    public static function write(string|array $log): void
    {
        self::printLog($log);
    }
    
    /**
     * ファイルにログを吐き出す
     * @param string|array $log 吐き出すログ
     * @return void
     */
    private static function printLog(string|array $log): void
    {
        $file = sprintf(
            '%s%s%s',
            LOG_PATH,
            self::$add_name,
            self::$name
        );
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
