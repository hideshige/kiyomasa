<?php
/**
 * パラメータのショートカット用スタティックオブジェクト
 *
 * @author   Sawada Hideshige
 * @version  1.0.0.0
 * @package  device
 * 
 */

namespace Php\Framework\Device;

class S
{
    public static $post; // 整形後のPOSTパラメータ
    public static $get; // 整形後のGETパラメータ
    public static $url; // URLパラメータ
    public static $dbm; // DBマスターモジュール
    public static $dbs; // DBスレーブモジュール
    public static $mem; // memcachedモジュール
    public static $disp; // テンプレートデータ
    public static $user; // セッション上のユーザーデータ
    public static $ouser; // ページに表示するユーザーデータ
    public static $jflag; // そのモデルがJSON形式かHTML形式か
}
