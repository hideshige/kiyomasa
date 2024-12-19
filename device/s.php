<?php
/**
 * 静的グローバルショートカットエイリアス
 *
 * @author   Sawada Hideshige
 * @version  1.0.2.1
 * @package  device
 * 
 */

namespace Php\Framework\Device;

class S
{
    public static array $post = []; // 整形後のPOSTパラメータ
    public static array $get = []; // 整形後のGETパラメータ
    public static array $url = []; // URLパラメータ
    public static object $dbm; // DBマスターモジュール
    public static object $dbs; // DBスレーブモジュール
    public static object $mem; // memcachedモジュール
    public static array $disp; // テンプレートデータ
    public static array $user; // セッション上のユーザーデータ
    public static array $ouser; // ページに表示するユーザーデータ
    public static bool $jflag; // JSON形式かHTML形式か
    public static array $header = []; // ヘッダーに指定があるか
}
