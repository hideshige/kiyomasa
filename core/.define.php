<?php
/**
 * 定義
 *
 * @author   Sawada Hideshige
 * @version  1.0.2.0
 * @package  core
 */

// php.iniで設定されていない場合ここで設定する
//date_default_timezone_set('Asia/Tokyo');
//mb_language('Japanese');
//mb_internal_encoding('utf8');

define('TIMESTAMP', date('Y-m-d H:i:s'));
$http_client_ip = filter_input(INPUT_SERVER, 'HTTP_CLIENT_IP');
$http_x_forwarded_for = filter_input(INPUT_SERVER, 'HTTP_X_FORWARDED_FOR');
if ($http_client_ip){
    $ip = $http_client_ip;
} elseif ($http_x_forwarded_for) {
    $ip = $http_x_forwarded_for;
} else {
    $ip = filter_input(INPUT_SERVER, 'REMOTE_ADDR');
}
define('IP_ADDRESS', $ip);
define('USER_AGENT', filter_input(INPUT_SERVER, 'HTTP_USER_AGENT'));
define('REFERER', filter_input(INPUT_SERVER, 'HTTP_REFERER'));

// UTF8 BOM文字列
const BOM_STR = "\xEF\xBB\xBF";

// curl用結果形式の定義
const CURL_TYPE_XML = 1;
const CURL_TYPE_JSON = 2;
const CURL_TYPE_TEXT = 3;

// セッションが起動しているかどうか
$g_session_flag = false;

// データベースデバッグ用表示カウンタ
$g_counter = 1;

// 使用すると問題のある文字
$g_change_chara = [];
$g_change_chara['&#36;'] = '$';// $マークの後に整数が来ると$マークと整数が消えてしまうためISO数値コードに変換しておく
$g_change_chara['&#xFF5E;'] = '～';// 文字化けしやすいためISO数値コードに変換しておく
$g_change_chara['&#92;'] = '\\';// \マーク

// 隔離性水準
// 処理内容に応じて使い分けること
const READ_UNCOMMITTED = 0;
// 「コミットされていないものを読み込む」
// 他のトランザクションを無視して読み取るというもの
// 他のトランザクションが編集中のデータを読み込んでしまう現象が発生する(ダーティリードという)
// 待ちが発生しないので一番処理速度が早い
const READ_COMMITTED = 1;
// 「コミットされているものを読み込む」
// 読み取るデータは、他のトランザクションで変更できるが、コミットされるまでは変更前の状態を読み込み、コミットされると変更後の状態を読み込む
// 繰り返し読み込みができないから、読み込みの再現性がない(ファジーリードという)
// READ_COMMITTEDでは、ダーティリードを防止することができる
const REPEATABLE_READ = 2;
// 「繰り返し読み込みを可能にする」
// 読み取るデータを占有ロックして、他のトランザクションで変更できないようにする
// 他のトランザクションはデータの追加はできるから、ないはずのデータが出現する矛盾が発生する(ファントムリードという)
// REPEATABLE_READでは、ダーティリードとファジーリードを防止することができる
const SERIALIZABLE = 3;
// 「直列化を可能にする」
// 関係するテーブルを共有ロック・占有ロックして実行する
// SERIALIZABLEでは、ダーティリードとファジーリードとファントムリードを防止することができる
// 処理を直列に並べて順々に実行していくので、並行処理ができず、他のトランザクションに待ちが発生するのがデメリット
// 絶対にデータに矛盾が出ては困る処理に利用する
// 割と早く終わることがわかっている処理のみに使う（トランザクション内にループ処理があるときは使うべきではない）

// 検索タイプ
const PARTIAL_MATCH = 0;
const FORWARD_MATCH = 1;
const EXACT_MATCH = 2;
const CRYPT_MATCH = 3;
