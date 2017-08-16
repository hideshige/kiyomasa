<?php
/**
 * PHPフレームワーク KIYOMASA
 *
 * @author   Sawada Hideshige
 * @version  1.0.1.0
 * @package  public_html
 * 
 * 標準コーディング規約
 * http://www.php-fig.org/
 * 
 */

$first_time = microtime(true);
$first_memory = memory_get_usage() / 1024;

// PHP環境の確認
if ((float)phpversion() < 7.1) {
    echo 'PHP ' . phpversion();
    exit;
} else if (!extension_loaded('mbstring')) {
    echo 'mbstringがインストールされていません';
    exit;
} else if (!extension_loaded('PDO')) {
    echo 'PDOがインストールされていません';
    exit;
}

header("P3P: CP='UNI CUR OUR'"); // コンパクトプライバシーポリシー
header('X-XSS-Protection: 1; mode=block'); // XSS対策
header('Content-Type: text/html;charset=UTF-8'); // 文字コード

// コントローラの読み込み
require_once(__DIR__ . '/../core/.define.php');
require_once(__DIR__ . '/../core/env.php');
require_once(__DIR__ . '/../core/config.php');
require_once(__DIR__ . '/../core/.castle.php');
new Php\Framework\Core\Castle();

/**
 * ダンプをバッファに保存してデバッグに表示する
 * "dump(ダンプしたい変数)"の形で利用する
 * @global string $dump ダンプ用バッファ
 * @param mixed $arguments 引数群（引数はカンマ区切りでいくつでも指定できる）
 * @return string
 */
$dump = '';
function dump(...$arguments): string
{
    global $dump;
    $bt = debug_backtrace();
    $dump .= sprintf("# %s {{DUMP_LINE}}%s\n",
        str_replace(SERVER_PATH, '', $bt[0]['file']), $bt[0]['line']);
    ob_start();
    foreach ($arguments as $v) {
        var_dump($v);
    }
    $dump .= ob_get_clean();
    return $dump;
}
