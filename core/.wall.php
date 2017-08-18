<?php
/**
 * ウォール　デバッグ部
 *
 * @author   Sawada Hideshige
 * @version  1.0.0.1
 * @package  core
 * 
 */

namespace Php\Framework\Core;

use Php\Framework\Device\{S, View};

trait Wall
{
    /**
     * デバッグ情報の成形
     * @global float $first_memory
     * @global float $first_time
     * @global string $dump
     * @return string|array
     */
    private function dispDebug()
    {
        $disp = '';
        if ($this->debug) {
            ob_start();
            if (S::$post) {
                var_dump(S::$post);
            }
            $post = ob_get_clean();
            ob_start();
            if (S::$get) {
                var_dump(S::$get);
            }
            $get = ob_get_clean();
            ob_start();
            if (S::$url) {
                var_dump(S::$url);
            }
            $url = ob_get_clean();
            ob_start();
            if ($_FILES) {
                var_dump($_FILES);
            }
            $files = ob_get_clean();
            ob_start();
            if ($_COOKIE) {
                var_dump($_COOKIE);
            }
            $cookie = ob_get_clean();
            ob_start();
            if ($_SESSION) {
                var_dump($_SESSION);
            }
            $session = ob_get_clean();

            global $first_memory;
            global $first_time;
            global $dump;

            $peak_memory = memory_get_peak_usage() / 1024;
            $last_time = microtime(true);
            
            // ENV定数に対応
            $env = ['ローカル', '開発環境', '検証環境', '本番環境'];
            
            $navi_id = microtime(true);
            
            $debug = [
                'request_url' => filter_input(INPUT_SERVER, 'REQUEST_URI'),
                'os' => PHP_OS,
                'php_ver' => phpversion(),
                'memory1' => number_format($first_memory),
                'memory2' => number_format($peak_memory - $first_memory),
                'memory3' => number_format($peak_memory),
                'ip' => IP_ADDRESS,
                'user_agent' => USER_AGENT,
                'timestamp' => TIMESTAMP,
                'time' => time(),
                'db_slave' => $this->modDebugSql(S::$dbs->getSql(true)),
                'db_master' => $this->modDebugSql(S::$dbm->getSql(true)),
                'memcached' => nl2br(htmlspecialchars(S::$mem->getDispMem())),
                'post' => $this->modDebugDump($post),
                'get' => $this->modDebugDump($get),
                'url' => $this->modDebugDump($url),
                'files' => $this->modDebugDump($files),
                'session' => $this->modDebugDump($session),
                'cookie' => $this->modDebugDump($cookie),
                'namespace' => NAME_SPACE,
                'dump' => $this->modDebugDump($dump),
                'debug_disp' => $dump ? 'block' : 'none',
                'navi_id' => $navi_id
            ];
            
            $process = round($last_time - $first_time, 5);
            
            $view = [];
            
            if (S::$jflag) {
                $debug['disp_type'] = 'ajax';
                $view['DEBUG_INCLUDE'][0] = $debug;
                $view2 = [];
                $view2['AJAX_NAVI'][0]['process'] = $process;
                $view2['AJAX_NAVI'][0]['navi_id'] = $navi_id;
                $disp = [];
                $disp['navi_id'] = $navi_id;
                $disp['debug'] =
                    View::template('element/.debug_include.tpl', $view);
                $disp['navi'] =
                    View::template('element/.debug_ajax_navi.tpl', $view2);
            } else {
                $debug['disp_type'] = 'html';
                $view['DEBUG'][0]['DEBUG_INCLUDE'][0] = $debug;
                $view['REPLACE']['env'] = $env[ENV] ?? 'ENV' . ENV;
                $view['REPLACE']['process'] = $process;
                $view['REPLACE']['navi_id'] = $navi_id;
                $disp = View::template('.debug.tpl', $view);
            }
        }
        return $disp;
    }
    
    /**
     * SQLデバッグの成型
     * @param string $text SQL文字列
     * @return string
     */
    private function modDebugSql(string $text): string
    {
        $text = htmlspecialchars((string)$text);
        preg_match_all("/{{STRING}}'(.*?)'/s", $text, $match);
        if (isset($match[1])) {
            foreach ($match[1] as $v) {
                $text = preg_replace("/{{STRING}}'" . preg_quote($v, '/')
                     . "'/s", '&apos;<span class="fw_debug_bold fw_debug_str">'
                    // 文字列として使用されているコロンを置換しておく
                    . preg_replace('/:/', '{{COLON}}', $v)
                    . '</span>&apos;', $text);
            }
        }
        preg_match_all(
            '/═══ BEGIN ROW ═══(.*?)═══ END ROW ═══/s', $text, $match);
        if (isset($match[1])) {
            foreach ($match[1] as $v) {
                $text = preg_replace('/═══ BEGIN ROW ═══' . preg_quote($v, '/')
                    . '═══ END ROW ═══/s', '<span name="fw_debug_process" '
                    . 'class="fw_debug_bold fw_debug_db_select">'
                    // 文字列として使用されているコロンを置換しておく
                    . preg_replace('/:/', '{{COLON}}', $v) . '</span>', $text);
            }
        }
        $text = preg_replace('/{{AT}}@(\w*)/',
            '@<span class="fw_debug_bold">$1</span>', $text);
        $text = preg_replace('/{{NULL}}NULL/',
            '<span class="fw_debug_bold fw_debug_null">NULL</span>', $text);
        $text = preg_replace('/{{INT}}([\d\-\.]*)/',
            '<span class="fw_debug_bold fw_debug_int">$1</span>', $text);
        $text = preg_replace('/{{STATEMENT}}(\w*)/',
            '<span class="fw_debug_bold fw_debug_stmt">$1</span>', $text);
        $text = preg_replace('/:(\w*)/',
            '<span name="fw_debug_process_qu" class="fw_debug_bold"'
            . ' style="display: none;">?</span>'
            . '<span name="fw_debug_process" class="fw_debug_bold">:$1</span>',
            $text);
        $text = preg_replace("/{{COLON}}/", ':', $text);
        $text = preg_replace("/{{COUNTER (\d*)}}/",
            '<span name="fw_debug_process" class="fw_debug_counter">$1</span> ',
            $text);
        $text = preg_replace("/{{TIME}}(.*?\])/",
            '<span name="fw_debug_process" class="fw_debug_time">$1</span>',
            $text);
        $text = preg_replace('/(LEFT JOIN|INNER JOIN|RIGHT JOIN|WHERE |SELECT |'
            . 'ORDER |LIMIT |UPDATE |INSERT |REPLACE |DELETE |VALUES )/',
            '<br />$1', $text);
        if ($text === '') {
            $text = 'Not Connected';
        }
        return nl2br($text);
    }
    
    /**
     * DUMPデバッグの成型
     * @param string|null $text DUMP文字列
     * @return string
     */
    private function modDebugDump($text): string
    {
        $text = htmlspecialchars((string)$text);
        $text = preg_replace('/# (.*){{DUMP_LINE}}(\d*)/',
            '<span class="fw_debug_line">$1</span>'
            . '<span class="fw_debug_bold fw_debug_line">$2</span>', $text);
        $text = preg_replace('/{{ERROR_INFO}}(.*)/',
            '<span class="fw_debug_bold fw_debug_str">$1</span>', $text);
        $text = preg_replace('/NULL/',
            '<span class="fw_debug_null">NULL</span>', $text);
        $text = preg_replace('/\[&quot;(.*?)&quot;\]/',
            '[&quot;<span class="fw_debug_bold">$1</span>&quot;]', $text);
        $text = preg_replace('/string\((\d*)\) &quot;(.*?)&quot;/s',
            'string($1) &quot;<span class="fw_debug_bold fw_debug_str">'
            . '$2</span>&quot;', $text);
        $text = preg_replace('/int\((\d*)\)/', '<span class="fw_debug_int">'
            . 'int(<span class="fw_debug_bold">$1</span>)</span>', $text);
        return $text;
    }
    
    /**
     * JSON用デバッグの表示
     * @global string $dump DUMPデータ
     * @param array $json JSON参照渡し
     * @return void
     */
    private function jsonDebug(array &$json): void
    {
        if ($this->debug) {
            $arr = $this->dispDebug();
            $debug = [];
            $debug['node'] = $arr['debug'];
            $debug['node_id'] = 'fw_debug_area_ajax_box_' . $arr['navi_id'];
            $debug['node_class'] = 'fw_debug_area_ajax_box';
            $debug['node_add'] = 1;
            $debug['node_tag'] = 'div';
            $navi = [];
            $navi['node'] = $arr['navi'];
            $navi['node_id'] = 'fw_debug_guide_ajax_' . $arr['navi_id'];
            $navi['node_class'] = 'fw_debug_guide_ajax';
            $navi['node_add'] = 1;
            $navi['node_tag'] = 'div';
            $json['fw_debug_include_ajax'] = [$debug];
            $json['fw_debug_guide'] = [$navi];
        }
    }
}