<?php
/**
 * PHPフレームワーク KIYOMASA
 *
 * @author   Sawada Hideshige
 * @version  1.0.3.6
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
if ((float)phpversion() < 7.2) {
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

// コントローラの読み込み
require_once(__DIR__ . '/../core/.define.php');
require_once(__DIR__ . '/../core/env.php');
require_once(__DIR__ . '/../core/config.php');
require_once(__DIR__ . '/../core/.castle.php');
new Php\Framework\Core\Castle();

/**
 * ダンプをバッファに保存してデバッグに表示する
 * "dump(ダンプしたい変数)"の形で利用する
 * @global string $g_dump ダンプ用バッファ
 * @param mixed $arguments 引数群（引数はカンマ区切りでいくつでも指定できる）
 * @return string
 */
function dump(...$arguments): string
{
    global $g_dump;
    $bt = debug_backtrace();
    $g_dump .= sprintf("# %s {{DUMP_LINE}}%s\n",
        str_replace(SERVER_PATH, '', $bt[0]['file']), $bt[0]['line']);
    ob_start();
    foreach ($arguments as $v) {
        var_dump($v);
    }
    $g_dump .= ob_get_clean();
    return $g_dump;
}
$g_dump = '';

/**
 * トレース
 * トレースしたい場所で"trace('識別子')"の形で利用する
 * @global array $g_trace トレース用バッファ
 * @param string $id 識別子
 * @return void
 */
function trace(string $id = ''): void
{
    global $g_trace;
    static $num = 0;
    $backtrace = debug_backtrace();
    $i = 0;
    
    end($backtrace);
    do {
        $cur = current($backtrace);
        if (empty($cur['file']) or
            preg_match('/\.router\.php$/', $cur['file'])) {
            continue;
        }
        
        $match = $match2 = [];
        preg_match('/^(.*\\\)(.*)$/', $cur['class'] ?? '', $match);
        $namespace = trim($match[1] ?? '-', '\\');
        $class_name = $match[2] ?? '-';
        $ref = '';
        if ($class_name !== '-') {
            $ref = ReflectionMethod::export(
                $cur['class'], $cur['function'], true);
        } else {
            $ref = ReflectionFunction::export($cur['function'], true);
        }
        // ソースからメソッドのコメントを抽出
        preg_match('</\*\*(.*)?\*/>s', $ref, $match2);
        $comment = isset($match2[1]) ?
            preg_replace('/(.*)\* /', '', trim($match2[1])) : '';

        $g_trace['TRACE'][$num]['id'] = $id ? $id : $num + 1;
        $g_trace['TRACE'][$num]['TABLE_DATA'][$i] = [
            'trace_num' => $i + 1,
            'file_name' => str_replace(SERVER_PATH, '', $cur['file']),
            'line' => $cur['line'],
            'namespace' => $namespace,
            'class_name' => $class_name,
            'comment' => $comment,
            'call_method' => $cur['type'] ?? '',
            'function_name' => $cur['function'] ?? '-',
            'args' => trim(print_r($cur['args'] ?? '', true)),
        ];
        array_walk($g_trace['TRACE'][$num]['TABLE_DATA'][$i],
            function (&$var) {
                $var = filter_var($var,
                    FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: '';
            });
        $i ++;
    } while (prev($backtrace));
    $num ++;
}
$g_trace = [];
