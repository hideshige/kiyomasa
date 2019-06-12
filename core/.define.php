<?php
/**
 * 定義
 *
 * @author   Sawada Hideshige
 * @version  1.0.0.0
 * @package  core
 */

// php.iniで設定されていない場合ここで設定する
date_default_timezone_set('Asia/Tokyo');
mb_language('Japanese');
mb_internal_encoding('utf8');

define('TIMESTAMP', date('Y-m-d H:i:s'));
$http_client_ip = filter_input(INPUT_SERVER, 'HTTP_CLIENT_IP');
$http_x_forwarded_for = filter_input(INPUT_SERVER, 'HTTP_X_FORWARDED_FOR');
if ($http_client_ip){
    $ip = $http_client_ip;
} else if ($http_x_forwarded_for) {
    $ip = $http_x_forwarded_for;
} else {
    $ip = filter_input(INPUT_SERVER, 'REMOTE_ADDR');
}
define('IP_ADDRESS', $ip);
define('USER_AGENT', filter_input(INPUT_SERVER, 'HTTP_USER_AGENT'));
define('MOBILE_FLAG', preg_match(
    '/(iPhone|iPod|Android|BlackBerry|Windows\sPhone)/',
    USER_AGENT) ? true : false);
define('REFERER', filter_input(INPUT_SERVER, 'HTTP_REFERER'));

// form用パラメータ種別の定義
const PARAM_TYPE_TEXTAREA = 1;
const PARAM_TYPE_RADIO = 2;
const PARAM_TYPE_SELECT = 3;
const PARAM_TYPE_CHECK = 4;
const PARAM_TYPE_TEXT = 5;
const PARAM_TYPE_PASSWORD = 6;
const PARAM_TYPE_EMAIL = 7;
const PARAM_TYPE_META = 8;

// curl用結果形式の定義
const CURL_TYPE_XML = 1;
const CURL_TYPE_JSON = 2;
const CURL_TYPE_TEXT = 3;

// データベースデバッグ用表示カウンタ
$g_counter = 1;

// 開いている画面が管理画面かどうか
$g_admin_flag = false;

// 使用すると問題のある文字
$g_change_chara = [];
$g_change_chara['&#36;'] = '$';// $マークの後に整数が来ると$マークと整数が消えてしまうためISO数値コードに変換しておく
$g_change_chara['&#xFF5E;'] = '～';// 文字化けしやすいためISO数値コードに変換しておく
$g_change_chara['&#92;'] = '\\';// \マーク
