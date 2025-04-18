<?php
/**
 * プロジェクトの設定を記入する
 */

const NAME_SPACE = 'Yourname\Yourproject';

if (ENV === ENV_PHP) {
    // ビルトインウェブサーバ
    ini_set('display_errors', 'On');
    define('SERVER_PATH', 'D:\kiyomasa\\'); // Win
//    define('SERVER_PATH', '/Users/hideshige/Documents/Sites/kiyomasa/'); // Mac
    define('LOG_PATH', 'D:\kiyomasa\log\\');
//    define('LOG_PATH', '/Users/hideshige/Documents/Sites/kiyomasa/log/'); // Mac

    define('MEMCACHED_SERVER', 'localhost');
    define('DB_DRIVER', 'mysql');
    define('DB_MASTER_SERVER', 'localhost:3306');
    define('DB_MASTER_USER', 'kiyomasa');
    define('DB_MASTER_PASSWORD', 'password');
    define('DB_SLAVE_SERVER', 'localhost:3306');
    define('DB_SLAVE_USER', 'kiyomasa');
    define('DB_SLAVE_PASSWORD', 'password');
    define('DB_NAME', 'kiyomasa');

    define('FROM_EMAIL', '');
    define('EMAIL_RETURN_PATH', '');
    define('FROM_NAME', '');
    
    require_once('.debug.php');
} elseif (ENV === ENV_DEV) {
    // テスト環境
    ini_set('display_errors', 'On');
    define('SERVER_PATH', '/var/www/html/yoursite/');
    define('LOG_PATH', '/var/www/html/yoursite/log/');

    define('MEMCACHED_SERVER', 'localhost');
    define('DB_DRIVER', 'mysql');
    define('DB_MASTER_SERVER', 'localhost');
    define('DB_MASTER_USER', '');
    define('DB_MASTER_PASSWORD', '');
    define('DB_SLAVE_SERVER', 'localhost');
    define('DB_SLAVE_USER', '');
    define('DB_SLAVE_PASSWORD', '');
    define('DB_NAME', '');

    define('FROM_EMAIL', '');
    define('EMAIL_RETURN_PATH', '');
    define('FROM_NAME', '');
    
    require_once('.debug.php');
} else {
    // 本番環境
    ini_set('display_errors', 'Off');
    define('SERVER_PATH', '/var/www/html/yoursite/');
    define('LOG_PATH', '/var/log/yoursite_');

    define('MEMCACHED_SERVER', 'localhost');
    define('DB_DRIVER', 'mysql');
    define('DB_MASTER_SERVER', 'localhost');
    define('DB_MASTER_USER', '');
    define('DB_MASTER_PASSWORD', '');
    define('DB_SLAVE_SERVER', 'localhost');
    define('DB_SLAVE_USER', '');
    define('DB_SLAVE_PASSWORD', '');
    define('DB_NAME', '');

    define('FROM_EMAIL', '');
    define('EMAIL_RETURN_PATH', '');
    define('FROM_NAME', '');
}

define('COOKIE_LIFETIME', 60 * 60 * 24 * 30);

const PROJECT_PREFIX = ''; // プロジェクトを示す接頭辞

/*-------------- 暗号化 ----------------*/

const OPEN_SSL_PASSPHRASE = 'ssl_keyword1'; // OpenSSL用

// 以下の手順で鍵を生成しておく
// require_once(SERVER_PATH . 'device/equipment/openssl.php');
// $key = Php\Framework\Device\Equipment\Openssl::makeKey();
// echo $key[0] . $key[1];
const OPEN_SSL_PRIVATE_KEY = <<<EOT
-----BEGIN ENCRYPTED PRIVATE KEY-----
MIICxjBABgkqhkiG9w0BBQ0wMzAbBgkqhkiG9w0BBQwwDgQIMT7at7VImBsCAggA
MBQGCCqGSIb3DQMHBAgCmTxPmufFvQSCAoBqvkZBQ5rjubF5TbzFVfqAwKx3d7Sz
h52nDlY3B36HL4u0MhtWxGFWptHT8DlyDBiUKnLYGzt2bB2Ymb7FDwlrYtZbgHMa
4mhCo0FuvM1MLpzOLM/lzSdJy2fjANhn6+7UiEErR8osnbBmIKTzNQLn6ZUHJI32
lrPkiojZ9pboX7ptQ8afRg/kVV6xljaEX6rZJFXd0yYr5bZhrSPonG6spva2kXuj
LUIda7gTJz46/Oa2s79DQ7gQdll8lu6xrN3TzHbcIOt3rp6zNwjbdSwyaCqjXk1+
QC3gWY9gG2z3m/UsckMJ8WjaBuA7v6UEAgHmSGT9AnqOQQxIx3f+GqSRr6L2AZh0
zoWZuUztJwCUmIAtRwyET9eCNTx5rn0pBjBTAtawTtDsWJeZ/C+53KLp/ll+xiP1
gBYN5T/ROplD/E+DMSk/odD9nOHbSOWPkqXsULeeIWOqYwxOAlxQsO9SLx/e3qcY
W7LrpFFy1YsyOAFE+tv4TeeQsT4RrT80iyO+DPF6lTVGJ0/bkHU+j5PiHGz12BXg
XKr7ITRsTlES11LmpapkCfsEGf/HeLIEamfmlM7wTrPX8adcY7Uoi854ocvdXYR/
bIkSVNSk8+Y1NlSoKp8tJG0rXUNKaAg1b3tAoekLN41y6GVA3QQlN2y70GuiTI6F
qR8/rhsz0Un0B+S/t8KM7f8J4VkqtODeOvkcfsauq4jgLTnLK5siJmAbY1ULUwqF
PoMmrtcsSs2HH1u8/3MLyEVLW0JL3bbnC2mIl+0eFxvuU/JFXZSC5sT0tguvegbN
zWA2i3Wir+dxoH/ElUMRJF36t1yaYRpkfENiKTxwqp6s7Uu6sT8QdH1K
-----END ENCRYPTED PRIVATE KEY-----
EOT;
const OPEN_SSL_PUBLIC_KEY = <<<EOT
-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQC2HkZ9wInlTovcq07NSa5UOSHI
3rjRTKb5YJaZRzx1UsA/eC8cDwP24CvU68FBqFfkYM3lFWdQKKMBHva6VXfBU1XN
/7ii51meeFd2/tBTL0QOcm7utJsMO7pdeUpm69g6V5edpzoWeN0KT0/pEzEAd9DG
o/80+6TR6vFdGlDoxwIDAQAB
-----END PUBLIC KEY-----
EOT;

/*-------------- 以下は global で呼び出す共通パラメータ ----------------*/

// キャッシュするかどうか
$g_cache_flag = true;

// URLをフォルダで分ける場合、この配列に追加する
$g_folder = [];

$g_debug = (MODE >= MODE_DEBUG && ENV <= ENV_DEV) ? true : false;
