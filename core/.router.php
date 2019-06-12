<?php
/**
 * PHPビルトイン　ウェブサーバ設定
 * 
 * @author   Sawada Hideshige
 * @version  1.0.0.1
 * @package  core
 *
 * > php -S localhost:(ポート番号) -t /ファイルの場所/public_html /ファイルの場所/core/.router.php
 * でローカルマシンから実行可能
 */

// ウェブサーバの設定と同等の処理をPHPでここに記載する
$file = parse_url(filter_input(INPUT_SERVER, 'REQUEST_URI'), PHP_URL_PATH);
$doc_root = filter_input(INPUT_SERVER, 'DOCUMENT_ROOT');
if (file_exists($doc_root . $file)) {
    return false;
} else {
    $_GET['url'] = $file;
    require $doc_root . '/index.php';
    return true;
}
