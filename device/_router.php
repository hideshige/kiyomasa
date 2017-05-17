<?php
/**
 * PHPビルトイン　ウェブサーバ設定
 * 
 * @author   Hideshige Sawada
 * @version  1.0.0.0
 * @package  device
 *
 * プロジェクトのディレクトリに入り
 * > php -S localhost:8000 -t public_html device/_router.php
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
