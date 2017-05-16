<?php
/**
 * ウォール　追加関数など土台強化部
 *
 * @author   Hideshige Sawada
 * @version  1.0.0.0
 * @package  device
 * 
 */

namespace bunroku\kiyomasa\device;

use Exception;

/**
 * 例外処理クラス
 */
class FwException extends Exception
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
 * ダンプをバッファに保存
 * @global string $dump ダンプ用バッファ
 * @param mixed ダンプするデータをカンマ区切りで記入する
 */
$dump = '';
function dump()
{
    global $dump;
    $bt = debug_backtrace();
    $dump .= sprintf("%s %s\n", $bt[0]['file'], $bt[0]['line']);
    ob_start();
    foreach ($bt[0]['args'] as $v) {
        var_dump($v);
    }
    $dump .= ob_get_clean();
    return $dump;
}

/**
 *  オートロード
 */
spl_autoload_register(
    function ($class_name)
    {
        $arr = explode('\\', $class_name);
        if (!isset($arr[1])) {
            throw new FwException('Class Name Error: ' . $class_name);
        }
        $count = count($arr);
        $under = preg_replace(
            '/^_/',
            '',
            // スタッドリーキャップス記法をアンダースコア記法に変換
            strtolower(preg_replace('/([A-Z])/', '_$1', $arr[$count - 1]))
        );
        $file_name = SERVER_PATH . $arr[$count - 2] . '/' . $under . '.php';
        if (!file_exists($file_name)) {
            throw new FwException('Class File Not Found: ' . $file_name);
        }
        require $file_name;
    }
);