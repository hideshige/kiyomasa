<?php
/**
 * フロントプログラム
 *
 * @author   Sawada Hideshige
 * @version  1.0.4.0
 * @package  public_html
 * 
 * 標準コーディング規約
 * http://www.php-fig.org/
 * 
 */

$first_time = microtime(true);
$first_memory = memory_get_usage() / 1024;

// PHP環境の確認
/*
if ((float)phpversion() < 7.4) {
    echo 'PHP OLD VERSION: ' . phpversion();
    exit(0);
} else if (!extension_loaded('mbstring')) {
    echo 'mbstringがインストールされていません';
    exit(0);
}
*/

header("P3P: CP='UNI CUR OUR'"); // コンパクトプライバシーポリシー
header('X-XSS-Protection: 1; mode=block'); // XSS対策
header('Content-Security-Policy: reflected-xss block'); // XSS対策
header('X-Frame-Options: DENY'); // クリックジャック対策

// コントローラの読み込み
require_once(__DIR__ . '/../core/.define.php');
require_once(__DIR__ . '/../core/env.php');
require_once(__DIR__ . '/../core/config.php');
require_once(__DIR__ . '/../core/.castle.php');
new Php\Framework\Core\Castle();
