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

use Exception;

/**
 * フレームワーク固有の例外処理クラス
 */
class SystemException extends Exception
{
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
            throw new SystemException('Class Name Error: ' . $class_name);
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
            throw new SystemException(
                'Class File Not Found: ' . $file_name
            );
        }
        require $file_name;
    }
);
