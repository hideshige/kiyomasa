<?php
/**
 * タレット　強化コントローラ部
 *
 * @author   Sawada Hideshige
 * @version  1.0.3.3
 * @package  device
 * 
 */

namespace Php\Framework\Device;

class Turret
{
    public $debug = false; // デバッグモード
    private $error_flag = false; // 初回エラーかどうか（循環防止のため）
    
    /**
     * モデルを実行し、ビューにデータを渡す
     * @param string $pagename 実行するモデルの名前
     * @param string $folder モデルのフォルダ名
     * @return void
     */
    public function disp(string $pagename, string $folder = ''): void
    {
        try {
            // modelファイルの読み込み
            $file = SERVER_PATH . 'model/' . $folder . $pagename . '.php';
            if (!file_exists($file)) {
                throw new \Error($file . ' not found: '
                    . filter_input(INPUT_SERVER, 'REQUEST_URI'), 10);
            }
            require_once($file);
            $class_name = NAME_SPACE . '\Model\\' . trim(
                str_replace(' ', '', ucwords(str_replace(
                ['_', '/'], [' ', '\\'], $folder . $pagename))));
            
            $this->model($class_name);
        } catch (\Error $e) {
            $this->modelError($e);
        }
    }
    
    /**
     * モデルの実行
     * @param string $class_name
     * @return void
     * @throws \Error
     */
    private function model(string $class_name): void
    {
        $model = new $class_name;
        $res = $model->logic();
        if ($res === false) {
            throw new \Error($class_name . ' logic notice', 10);
        }
        
        // デバッグにセッションの動作を表示するため事前にセッションを閉じる
        session_write_close();
        
        if (!S::$jflag and isset($model->tpl) and count($model->tpl)) {
            foreach ($model->tpl as $tk => $tv) {
                echo View::template($tv,
                    isset(S::$disp[$tk]) ? S::$disp[$tk] : []);
            }
        }
        if (!S::$jflag) {
            echo $this->dispDebug();
        } else if (is_array($res)) {
            $json = $res;
            $this->jsonDebug($json);
            echo json_encode($json);
        }
    }
    
    /**
     * モデル実行例外エラー
     * @param \Error $e
     * @return void
     */
    private function modelError(\Error $e): void
    {
        if ($e->getCode() != 10) {
            $info = new ErrorInfo;
            $info->set($e->getMessage(), $e->getFile(), $e->getLine());
        }

        // エラーページの表示
        if (!S::$jflag) {
            if (!$this->error_flag) {
                // 循環防止のフラグ
                $this->error_flag = true;
                // エラー画面モデルの読み込み
                $this->disp('error_page', 'content/');
            } else {
                echo '循環エラー';
            }
        } else {
            $json = ['alert' => 'エラー'];
            echo json_encode($json);
        }
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
            $json['fw_debug_include_ajax'] = $arr['include'];
            $json['fw_debug_guide_ajax_time'] = $arr['process'] . '秒';
        }
    }
    
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
                'db_slave' => $this->modDebugSql(S::$dbs->disp_sql),
                'db_master' => $this->modDebugSql(S::$dbm->disp_sql),
                'memcached' => nl2br(htmlspecialchars(S::$mem->disp_mem)),
                'post' => $this->modDebugDump($post),
                'get' => $this->modDebugDump($get),
                'url' => $this->modDebugDump($url),
                'files' => $this->modDebugDump($files),
                'session' => $this->modDebugDump($session),
                'cookie' => $this->modDebugDump($cookie),
                'namespace' => NAME_SPACE,
                'dump' => $this->modDebugDump($dump),
                'debug_disp' => $dump ? 'block' : 'none'
            ];
            
            $process = round($last_time - $first_time, 5);
            
            $view = [];
            
            if (S::$jflag) {
                $debug['disp_type'] = 'ajax';
                $view['DEBUG_INCLUDE'][0] = $debug;
                $arr = [];
                $arr['process'] = $process;
                $arr['include'] = View::template(
                    'element/.debug_include.tpl',
                    $view
                );
                $disp = $arr;
            } else {
                $debug['disp_type'] = 'html';
                $view['DEBUG'][0]['DEBUG_INCLUDE'][0] = $debug;
                $view['REPLACE']['env'] = isset($env[ENV])
                    ? $env[ENV] : 'ENV' . ENV;
                $view['REPLACE']['process'] = $process;
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
    private function modDebugSql($text): string
    {
        $text = htmlspecialchars((string)$text);
        preg_match_all("/{{STRING}}'(.*?)'/", $text, $match);
        if (isset($match[1])) {
            foreach ($match[1] as $v) {
                $text = preg_replace(
                    "/{{STRING}}'" . preg_quote($v, '/') . "'/",
                    '&apos;<span class="fw_debug_bold fw_debug_str">'
                    // 文字列として使用されているコロンを置換しておく
                    . preg_replace('/:/', '{{COLON}}', $v)
                    . '</span>&apos;',
                    $text
                );
            }
        }
        $text = preg_replace(
            '/\n/',
            '{{BR}}',
            $text
        );
        preg_match_all('/═══ BEGIN ROW ═══(.*?)═══ END ROW ═══/', $text, $match);
        if (isset($match[1])) {
            foreach ($match[1] as $v) {
                $text = preg_replace(
                    '/═══ BEGIN ROW ═══' . preg_quote($v, '/')
                    . '═══ END ROW ═══/',
                    '<span name="fw_debug_process" '
                    . 'class="fw_debug_bold fw_debug_db_select">'
                    // 文字列として使用されているコロンを置換しておく
                    . preg_replace('/:/', '{{COLON}}', $v)
                    . '</span>',
                    $text
                );
            }
        }
        $text = preg_replace(
            '/{{AT}}@(\w*)/',
            '@<span class="fw_debug_bold">$1</span>',
            $text
        );
        $text = preg_replace(
            '/{{NULL}}NULL/',
            '<span class="fw_debug_bold fw_debug_null">NULL</span>',
            $text
        );
        $text = preg_replace(
            '/{{INT}}([\d\-\.]*)/',
            '<span class="fw_debug_bold fw_debug_int">$1</span>',
            $text
        );
        $text = preg_replace(
            '/{{STATEMENT}}(\w*)/',
            '<span class="fw_debug_bold fw_debug_stmt">$1</span>',
            $text
        );
        $text = preg_replace(
            '/:(\w*)/',
            '<span name="fw_debug_process_qu" class="fw_debug_bold"'
            . ' style="display: none;">?</span>'
            . '<span name="fw_debug_process" class="fw_debug_bold">:$1</span>',
            $text
        );
        $text = preg_replace("/{{COLON}}/", ':', $text);
        $text = preg_replace(
            "/{{COUNTER (\d*)}}/",
            '<span name="fw_debug_process" class="fw_debug_counter">$1</span> ',
            $text
        );
        $text = preg_replace(
            "/; {{TIME}}(.*?\])/",
            '; <span name="fw_debug_process" class="fw_debug_time">$1</span>',
            $text
        );
        $text = preg_replace(
            '/{{BR}}/',
            '<br />',
            $text
        );
        $text = preg_replace(
            '/(LEFT JOIN|INNER JOIN|RIGHT JOIN|WHERE |SELECT |'
            . 'ORDER |LIMIT |UPDATE |INSERT |REPLACE |DELETE |VALUES )/',
            '<br />$1',
            $text
        );
        return $text;
    }
    
    /**
     * DUMPデバッグの成型
     * @param string|null $text DUMP文字列
     * @return string
     */
    private function modDebugDump($text): string
    {
        $text = htmlspecialchars((string)$text);
        $text = preg_replace(
            '/# (.*){{DUMP_LINE}}(\d*)/',
            '<span class="fw_debug_line">$1</span>'
            . '<span class="fw_debug_bold fw_debug_line">$2</span>',
            $text
        );
        $text = preg_replace(
            '/{{ERROR_INFO}}(.*)/',
            '<span class="fw_debug_bold fw_debug_str">$1</span>',
            $text
        );
        $text = preg_replace(
            '/\n/',
            '{{BR}}',
            $text
        );
        $text = preg_replace(
            '/NULL/',
            '<span class="fw_debug_null">NULL</span>',
            $text
        );
        $text = preg_replace(
            '/\[&quot;(.*?)&quot;\]/',
            '[&quot;<span class="fw_debug_bold">$1</span>&quot;]',
            $text
        );
        $text = preg_replace(
            '/string\((\d*)\) &quot;(.*?)&quot;/',
            'string($1) &quot;<span class="fw_debug_bold fw_debug_str">'
            . '$2</span>&quot;',
            $text
        );
        $text = preg_replace(
            '/int\((\d*)\)/',
            '<span class="fw_debug_int">'
            . 'int(<span class="fw_debug_bold">$1</span>)'
            . '</span>',
            $text
        );
        $text = preg_replace(
            '/{{BR}}/',
            "\n",
            $text
        );
        return $text;
    }

    /**
     * サニタイズ
     * @param array|string $data
     * @return array|string
     */
    public function h($data)
    {
        if (is_array($data)) {
            foreach ($data as $k => $v) {
                $data[$k] = $this->h($v);
            }
        } else {
            //  改行コード以外のコントロールコードを排除
            $data = preg_replace(
                '/[\x00-\x09\x0B\x0C\x0E-\x1F\x7F]/', '', $data
            );
            // UNICODE不可視文字トリム
            $invisible_utf8_codes = array(
                '&#x00AD;',
                '&#x2000;','&#x2001;','&#x2002;','&#x2003;','&#x2004;',
                '&#x2005;','&#x2006;','&#x2007;','&#x2008;','&#x2009;',
                '&#x200A;','&#x200B;','&#x200C;','&#x200D;','&#x200E;',
                '&#x200F;','&#x2028;','&#x2029;','&#x202A;','&#x202B;',
                '&#x202C;','&#x202D;','&#x202E;','&#x202F;','&#x205F;',
                '&#x2060;','&#x2061;','&#x2062;','&#x2063;','&#x2064;',
                '&#x2065;','&#x2066;','&#x2067;','&#x2068;','&#x2069;',
                '&#x206A;','&#x206B;','&#x206C;','&#x206D;','&#x206E;',
                '&#x206F;','&#x2322;','&#x2323;','&#x2800;','&#x3164;',
                '&#xA717;','&#xA718;','&#xA719;','&#xA71A;',
                '&#xA720;','&#xA721;',
                '&#xFE00;','&#xFE01;','&#xFE02;','&#xFE03;','&#xFE04;',
                '&#xFE05;','&#xFE06;','&#xFE07;','&#xFE08;','&#xFE09;',
                '&#xFE0A;','&#xFE0B;','&#xFE0C;','&#xFE0D;','&#xFE0E;',
                '&#xFE0F;','&#xFEFF;',
                '&#xFFF0;','&#xFFF1;','&#xFFF2;','&#xFFF3;','&#xFFF4;',
                '&#xFFF5;','&#xFFF6;','&#xFFF7;','&#xFFF8;',
            );
            $invisible_strs = array_map(
                function ($code) {
                    return html_entity_decode($code, ENT_NOQUOTES, 'UTF-8');
                },
                $invisible_utf8_codes
            );
            $data = str_replace($invisible_strs, '', $data);
            $data = htmlspecialchars($data, ENT_QUOTES);
            global $g_change_chara;
            foreach ($g_change_chara as $ck => $cv) {
                $data = str_replace($cv, $ck, $data);
            }
        }
        return $data;
    }
}
