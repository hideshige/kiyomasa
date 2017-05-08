<?php
/**
 * 定義一覧
 *
 * @author   Hideshige Sawada
 * @version  1.0.0.0
 * @package  common
 */

//php.iniで設定されていない場合ここで設定する
date_default_timezone_set('Asia/Tokyo');
define('DEFAULT_CHARSET', 'utf8');
mb_language('Japanese');
mb_internal_encoding(DEFAULT_CHARSET);

if (ENV == 0) {
  ini_set('display_errors', 1);
  define('SERVER_PATH', '/var/www/html/yoursite/');
  
  define('DOMAIN_NAME', '/');
  define('LINK_DOMAIN_NAME', 'http://dev.yoursite/');
  define('SSL_LINK_DOMAIN_NAME', 'https://dev.yoursite/');

  define('MEMCACHED_SERVER', 'localhost');
  define('DB_MASTER_SERVER', 'localhost');
  define('DB_MASTER_USER', '');
  define('DB_MASTER_PASSWORD', '');
  define('DB_MASTER_NAME', '');
  define('DB_SLAVE_SERVER', 'localhost');
  define('DB_SLAVE_USER', '');
  define('DB_SLAVE_PASSWORD', '');
  define('DB_SLAVE_NAME', '');
  
  define('TO_EMAIL', '');
  define('FROM_EMAIL', '');
  define('EMAIL_RETURN_PATH', '');
  define('FROM_NAME', '');
} else if (ENV == 1) {
  define('SERVER_PATH', '/var/www/html/yoursite/');
  
  define('DOMAIN_NAME', '/');
  define('LINK_DOMAIN_NAME', 'http://yoursite/');
  define('SSL_LINK_DOMAIN_NAME', 'https://yoursite/');

  define('MEMCACHED_SERVER', 'localhost');
  define('DB_MASTER_SERVER', 'localhost');
  define('DB_MASTER_USER', '');
  define('DB_MASTER_PASSWORD', '');
  define('DB_MASTER_NAME', '');
  define('DB_SLAVE_SERVER', 'localhost');
  define('DB_SLAVE_USER', '');
  define('DB_SLAVE_PASSWORD', '');
  define('DB_SLAVE_NAME', '');
  
  define('TO_EMAIL', '');
  define('FROM_EMAIL', '');
  define('EMAIL_RETURN_PATH', '');
  define('FROM_NAME', '');
} else {
  define('SERVER_PATH', '/var/www/html/yoursite/');
  
  define('DOMAIN_NAME', '/');
  define('LINK_DOMAIN_NAME', 'http://yoursite/');
  define('SSL_LINK_DOMAIN_NAME', 'https://yoursite/');

  define('MEMCACHED_SERVER', 'localhost');
  define('DB_MASTER_SERVER', 'localhost');
  define('DB_MASTER_USER', '');
  define('DB_MASTER_PASSWORD', '');
  define('DB_MASTER_NAME', '');
  define('DB_SLAVE_SERVER', 'localhost');
  define('DB_SLAVE_USER', '');
  define('DB_SLAVE_PASSWORD', '');
  define('DB_SLAVE_NAME', '');
  
  define('TO_EMAIL', '');
  define('FROM_EMAIL', '');
  define('EMAIL_RETURN_PATH', '');
  define('FROM_NAME', '');
}

define('OPEN_SSL_PASSPHRASE', '');

define('COOKIE_LIFETIME', 60 * 60 * 24 * 30);
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
define('MOBILE_FLAG', preg_match('/(iPhone|iPod|Android|BlackBerry|Windows Phone)/', USER_AGENT) ? true : false);
define('REFERER', filter_input(INPUT_SERVER, 'HTTP_REFERER'));
define('USLEEP_TIME', 1);
define('AUTO_UPDATE_TIME', 1);//DBに作成・更新日時を自動保存する場合1
define('PROJECT_PREFIX', '');//プロジェクトを示す接頭辞

/*-------------- 以下は global で呼び出す共通パラメータ ----------------*/

//データベースデバッグ用表示カウンタ
$g_counter = 1;

//開いている画面が管理画面かどうか
$g_admin_flag = false;

//モデルとテンプレートに追加するフォルダ
$g_folder = array ('admin');

//メンテ突破IPアドレス
$g_ip_address = array();
$g_ip_address[] = '192.168.1.2';

//使用すると問題のある文字（public_html/index.phpとequipment/word_tag.phpで変換する）
$g_change_chara = array ();
$g_change_chara['&#36;'] = '$';//$マークの後に整数が来ると$マークと整数が消えてしまうためISO数値コードに変換しておく
$g_change_chara['&#xFF5E;'] = '～';//文字化けしやすいためISO数値コードに変換しておく（word_tagモジュールと対になっている）
$g_change_chara['&#92;'] = '\\';//\マーク

