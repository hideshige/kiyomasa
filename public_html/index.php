<?php
/**
 * フロントプログラム
 *
 * @author   Sawada Hideshige
 * @version  1.0.4.3
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
header('X-Frame-Options: DENY'); // クリックジャック対策

require_once(__DIR__ . '/../core/.define.php'); // システム定義
require_once(__DIR__ . '/../core/env.php'); // 環境識別
require_once(__DIR__ . '/../core/mode.php'); // モード識別
require_once(__DIR__ . '/../core/config.php'); // プロジェクト設定
require_once(__DIR__ . '/../core/.castle.php'); // コントローラ
new Php\Framework\Core\Castle();
