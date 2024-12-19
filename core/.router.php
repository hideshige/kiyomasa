<?php
/**
 * PHPビルトイン　ウェブサーバ設定
 * 
 * @author   Sawada Hideshige
 * @version  1.0.1.1
 * @package  core
 *
 * > php -S localhost:(ポート番号) -t /ファイルの場所/public_html /ファイルの場所/core/.router.php
 * でローカルマシンから実行可能
 */

// ウェブサーバの設定と同等の処理をPHPでここに記載する
date_default_timezone_set('Asia/Tokyo');
$file = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$doc_root = $_SERVER['DOCUMENT_ROOT'] ?? '';
if (file_exists($doc_root . $file)) {
    return false;
} else {
    $_GET['url'] = $file;
    require $doc_root . '/index.php';
    return true;
}
