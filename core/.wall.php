<?php
/**
 * ウォール　デバッグ部
 *
 * @author   Sawada Hideshige
 * @version  1.0.2.0
 * @package  core
 * 
 */

namespace Php\Framework\Core;

use Php\Framework\Device\{S, View};

trait Wall
{
    private $debug_json = []; // JSONデバッグ用
    
    /**
     * デストラクタ
     */
    public function __destruct()
    {
        if ($this->debug and S::$jflag === false) {
            echo $this->dispDebug();
        }
    }
    
    /**
     * デバッグ情報の成形
     * @global float $first_memory
     * @global float $first_time
     * @global string $g_dump
     * @global array $g_trace
     * @return string|array
     */
    private function dispDebug()
    {
        // デバッグにセッションの動作を表示するため事前にセッションを閉じる
        session_write_close();
        
        $disp = '';
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
        ob_start();
        if ($this->debug_json) {
            var_dump($this->debug_json);
        }
        $json = ob_get_clean();

        global $first_memory;
        global $first_time;
        global $g_dump;
        global $g_trace;

        $peak_memory = memory_get_peak_usage() / 1024;
        $last_time = microtime(true);

        $navi_id = microtime(true);

        $debug = [
            'request_url' => filter_input(INPUT_SERVER, 'REQUEST_URI'),
            'os' => PHP_OS,
            'php_ver' => phpversion(),
            'web_server' => filter_input(INPUT_SERVER, 'SERVER_SOFTWARE')
                . '　' . php_sapi_name(),
            'memory1' => number_format($first_memory),
            'memory2' => number_format($peak_memory - $first_memory),
            'memory3' => number_format($peak_memory),
            'ip' => IP_ADDRESS,
            'user_agent' => USER_AGENT,
            'timestamp' => TIMESTAMP,
            'time' => time(),
            'db_slave' => $this->modDebugSql(S::$dbs->getSql()),
            'db_master' => $this->modDebugSql(S::$dbm->getSql()),
            'memcached' => nl2br(htmlspecialchars(S::$mem->getDispMem())),
            'post' => $this->modDebugDump((string)$post),
            'get' => $this->modDebugDump((string)$get),
            'url' => $this->modDebugDump((string)$url),
            'files' => $this->modDebugDump((string)$files),
            'session' => $this->modDebugDump((string)$session),
            'cookie' => $this->modDebugDump((string)$cookie),
            'namespace' => NAME_SPACE,
            'dump' => $this->modDebugDump((string)$g_dump),
            'json' => $this->modDebugDump((string)$json),
            'trace' =>
                View::template('include/.debug_trace.tpl', $g_trace ?? []),
            'debug_disp' => $g_dump ? 'block' : 'none',
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
                View::template('include/.debug_include.tpl', $view);
            $disp['navi'] =
                View::template('include/.debug_ajax_navi.tpl', $view2);
        } else {
            $debug['disp_type'] = 'html';
            $view['DEBUG'][0]['DEBUG_INCLUDE'][0] = $debug;
            $view['REPLACE']['process'] = $process;
            $view['REPLACE']['navi_id'] = $navi_id;
            $disp = View::template('.debug.tpl', $view);
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
        $text = filter_var($text, FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: '';
        preg_match_all("/{{STRING}}&#039;(.*?)&#039;/s", $text, $match);
        if (isset($match[1])) {
            foreach ($match[1] as $v) {
                $text = preg_replace("/{{STRING}}&#039;"
                    . preg_quote($v, '/') . "&#039;/s",
                    '&apos;<span class="fw_debug_bold fw_debug_str">'
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
     * @param string $text DUMP文字列
     * @return string
     */
    private function modDebugDump(string $text): string
    {
        $text = filter_var($text, FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: '';
        $text = preg_replace('/#\s(.*){{DUMP_LINE}}(\d*)/',
            '<span class="fw_debug_line">$1</span>'
            . '<span class="fw_debug_bold fw_debug_line">$2</span>', $text);
        $text = preg_replace('/{{ERROR_INFO}}(.*)/',
            '<span class="fw_debug_bold fw_debug_str">$1</span>', $text);
        $text = preg_replace('/NULL/',
            '<span class="fw_debug_null">NULL</span>', $text);
        $text = preg_replace('/\[&quot;(.*?)&quot;\]/',
            '[&quot;<span class="fw_debug_bold">$1</span>&quot;]', $text);
        $text = preg_replace('/string\((\d*)\)\s&quot;(.*?)&quot;\n/s',
            'string($1) &quot;<span class="fw_debug_bold fw_debug_str">'
            . "$2</span>&quot;\n", $text);
        $text = preg_replace('/int\((\d*)\)/', '<span class="fw_debug_int">'
            . 'int(<span class="fw_debug_bold">$1</span>)</span>', $text);
        return $text;
    }
    
    /**
     * JSON用デバッグの表示
     * @param array $json JSON参照渡し
     * @return void
     */
    private function jsonDebug(array &$json): void
    {
        if ($this->debug) {
            $this->debug_json = $json;
            
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
