<?php
/**
 * エラー情報モジュール
 *
 * @author   Sawada Hideshige
 * @version  1.0.1.1
 * @package  device
 * 
 */

namespace Php\Framework\Device;

class ErrorInfo
{
    /**
     * エラー情報のセット
     * @global string $g_dump
     * @param string $message
     * @param string $file
     * @param int $line
     * @return void
     */
    public function set(string $message, string $file, int $line): void
    {
        S::$dbm->rollback();
        S::$dbm->unlock();
        
        // ログに記録し、開発環境の場合デバッグを表示
        $short_file = str_replace(SERVER_PATH, '', $file);
        $error = sprintf('%s(%d) %s', $short_file, $line, $message);
        Log::error($error);

        trace(sprintf('%s<br />発生場所 %s <strong>%s</strong>',
            $message, $short_file, $line));
        
        global $g_dump;
        $g_dump .= sprintf('# %s {{DUMP_LINE}}%d%s{{ERROR_INFO}}%s%s',
            $short_file, $line, PHP_EOL, $message, PHP_EOL);
    }
}
