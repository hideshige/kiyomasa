<?php
/**
 * ストーンウォール　追加関数など
 *
 * @author   Hideshige Sawada
 * @version  1.0.0.0
 * @package  extension
 * 
 */

namespace bts;

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
 * アンダースコア記法をスタッドリーキャップス記法に変換
 * @param string $string
 * @return string
 */
function className($string)
{
    return str_replace(' ', '', ucwords(str_replace('_', ' ', $string)));
}

// libraryのオートロード
spl_autoload_register(
    function ($className)
    {
        $fileName = SERVER_PATH . 'library/' . ltrim($className, '\\') . '.php';
        require $fileName;
    }
);