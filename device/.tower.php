<?php
/**
 * タワー　例外処理やショートカットなど土台強化部
 *
 * @author   Sawada Hideshige
 * @version  1.0.0.0
 * @package  device
 * 
 */

namespace Php\Framework\Device;

use \Exception;
use \Error;

class SystemError
{
    /**
     * エラー発生したときの処理
     * @param object $exception Exceptionオブジェクト
     * @param string $disp_message 画面表示用メッセージ
     */
    public static function setInfo($exception, $disp_message = '')
    {   
        S::$dbm->rollback();
        S::$dbm->unlock();

        // ログに記録し、開発環境の場合デバッグを表示
        $file = str_replace(SERVER_PATH, '', $exception->getFile());
        $line = $exception->getLine();
        $info = $exception->getMessage();
        $error = sprintf('%s(%s) %s', $file, $line, $info);
        Log::error($error);
        global $dump;
        $dump .= sprintf("# %s {{DUMP_LINE}}%s\n{{ERROR_INFO}}%s\n",
            $file, $line, $info);
        
        if ($exception->getCode() === 0) {
            // セッションにエラーメッセージを記録
            $_SESSION['error_message'] = $disp_message;
        }
    }
}

/**
 * ユーザー操作による例外処理クラス
 */
class UserException extends Exception
{
}

/**
 * パラメータのショートカット用スタティックオブジェクト
 *
 */
class S
{
    static $post; // 整形後のPOSTパラメータ
    static $get; // 整形後のGETパラメータ
    static $url; // URLパラメータ
    static $dbm; // DBマスターモジュール
    static $dbs; // DBスレーブモジュール
    static $mem; // memcachedモジュール
    static $disp; // テンプレートデータ
    static $user; // セッション上のユーザーデータ
    static $ouser; // ページに表示するユーザーデータ
    static $jflag; // そのモデルがJSON形式かHTML形式か
}


/**
 * クラスファイルのオートロード
 */
spl_autoload_register(
    function ($class_name)
    {
        $arr = explode('\\', $class_name);
        if (!isset($arr[1])) {
            throw new Error('Class Name Error: ' . $class_name);
        }
        $count = count($arr);
        $under = preg_replace(
            '/^_/',
            '',
            // スタッドリーキャップス記法をアンダースコア記法に変換
            preg_replace('/([A-Z])/', '_$1', $arr[$count - 1])
        );
        $file_name = SERVER_PATH
            . strtolower($arr[$count - 2] . '/' . $under) . '.php';
        if (!file_exists($file_name)) {
            throw new Error(
                'Class File Not Found: ' . $file_name
            );
        }
        require $file_name;
    }
);
