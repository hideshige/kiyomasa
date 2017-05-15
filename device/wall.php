<?php
/**
 * ウォール　追加関数など土台強化部
 *
 * @author   Hideshige Sawada
 * @version  1.0.0.0
 * @package  device
 * 
 */

namespace kiyomasa;

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
    return trim(str_replace(' ', '', ucwords(str_replace('_', ' ', $string))));
}

// extensionのオートロード
spl_autoload_register(
    function ($class_name)
    {
        //スタッドリーキャップス記法をアンダースコア記法に変換
        $under = preg_replace(
            // 名前空間部を取り除く
            '/^.*\\\\_|^.*\\\\/',
            '',
            // スタッドリーキャップス記法をアンダースコア記法に変換
            strtolower(preg_replace('/([A-Z])/', '_$1', $class_name))
        );
        $file_name = SERVER_PATH . 'extension/' . $under . '.php';
        require $file_name;
    }
);