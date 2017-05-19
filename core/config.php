<?php
/**
 * プロジェクトの設定を記入する
 */

const NAME_SPACE = 'Yourname\Yourproject';

if (ENV === 0) {
    // ビルトインウェブサーバ
    ini_set('display_errors', 1);
    define('SERVER_PATH', 'D:\kiyomasa\\'); // Win
//    define('SERVER_PATH', '/Users/hideshige/Documents/Sites/kiyomasa/'); // Mac

    define('DOMAIN_NAME', '/');
    define('LINK_DOMAIN_NAME', 'http://localhost:8000/');
    define('SSL_LINK_DOMAIN_NAME', 'http://localhost:8000/');

    define('MEMCACHED_SERVER', 'localhost');
    define('DB_MASTER_SERVER', 'localhost:3306');
    define('DB_MASTER_USER', 'kiyomasa');
    define('DB_MASTER_PASSWORD', 'password');
    define('DB_MASTER_NAME', 'kiyomasa');
    define('DB_SLAVE_SERVER', 'localhost:3306');
    define('DB_SLAVE_USER', 'kiyomasa');
    define('DB_SLAVE_PASSWORD', 'password');
    define('DB_SLAVE_NAME', 'kiyomasa');

    define('TO_EMAIL', '');
    define('FROM_EMAIL', '');
    define('EMAIL_RETURN_PATH', '');
    define('FROM_NAME', '');
} else if (ENV === 1) {
    // テスト環境
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
    // 本番環境
    ini_set('display_errors', 0);
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

define('COOKIE_LIFETIME', 60 * 60 * 24 * 30);

const AUTO_UPDATE_TIME = 1;// DBに作成・更新日時を自動保存する場合1
const PROJECT_PREFIX = '';// プロジェクトを示す接頭辞
const OPEN_SSL_PASSPHRASE = '';

/*-------------- 以下は global で呼び出す共通パラメータ ----------------*/

// モデルとテンプレートに追加するフォルダ
$g_folder = ['admin'];

// メンテ突破IPアドレス
$g_ip_address = [];
$g_ip_address[] = '192.168.1.2';
