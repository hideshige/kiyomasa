<?php
/**
 * ログ モジュール
 *
 * @author   Sawada Hideshige
 * @version  1.0.3.2
 * @package  device
 *
 */

namespace Php\Framework\Device;

class Log
{
    public static $batch = '';

    /**
     * システム上のエラーログを記録する
     * @param string|array $log 吐き出すログ
     * @return void
     */
    public static function error($log): void
    {
        $file = sprintf(
            '%slog/%serror%s.log',
            SERVER_PATH,
            self::$batch,
            date('ymd')
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
            '%slog/%s%s.log',
            SERVER_PATH,
            self::$batch,
            date('ymd')
        );
        self::printLog($log, $file);
    }

    /**
     * ファイルにログを吐き出す
     * @param string|array $log 吐き出すログ
     * @param string $file ファイルパス
     * @return void
     */
    private static function printLog($log, string $file): void
    {
        if (is_array($log)) {
            ob_start();
            var_dump($log);
            $log = ob_get_clean();
        }
        $res = sprintf("%s [%s] %s\n", date('H:i:s'), IP_ADDRESS, $log);
        error_log($res, 3, $file);
    }
}
