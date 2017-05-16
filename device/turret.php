<?php
/**
 * タレット　強化コントローラ部
 *
 * @author   Hideshige Sawada
 * @version  1.0.0.0
 * @package  device
 * 
 */

namespace bunroku\kiyomasa\device;

class Turret
{
    public $debug; // デバッグモード
    private $error_flag = false; // 初回エラーかどうか（循環防止のため）
    
    /**
     * モデルを実行し、ビューにデータを渡す
     * @param string $pagename 実行するモデルの名前
     * @param string $folder モデルのフォルダ名
     */
    public function disp($pagename, $folder = '')
    {
        try {
            // modelファイルの読み込み
            $file = SERVER_PATH . 'models/' . $pagename . '.php';
            if (!file_exists($file)) {
                throw new FwException($file . ' not found');
            }
            require_once($file);
            $class_name = NAME_SPACE . '\models\\' . trim(
                str_replace(' ', '', ucwords(str_replace('_', ' ', $pagename)))
            );
            $model = new $class_name;
            $res = $model->logic();
            if ($res === false) {
                throw new FwException($pagename . ' logic notice');
            }
            
            session_write_close();

            if (!S::$jflag) {
                if (isset($model->tpl) and count($model->tpl)) {
                    foreach ($model->tpl as $tk => $tv) {
                        echo View::template($tv, S::$disp[$tk], $folder);
                    }
                }
                $this->dispDebug();
            } else {
                if (is_array($res)) {
                    $json = $res;
                    if ($this->debug) {
                        global $dump;
                        $json['debug'] = "【DB SLAVE】\n" . S::$dbs->disp_sql;
                        $json['debug'] .= "----------------------------------------------------------------------\n";
                        $json['debug'] .= "【DB MASTER】\n" . S::$dbm->disp_sql;
                        $json['debug'] .= "----------------------------------------------------------------------\n";
                        $json['debug'] .= "【MEMCACHED】\n" . S::$mem->disp_mem;
                        $json['debug'] .= "----------------------------------------------------------------------\n";
                        $json['debug'] .= "【DUMP】\n" . $dump;
                        $json['debug'] .= "----------------------------------------------------------------------\n";
                        $json['debug'] .= "【MEMORY】\n" . number_format(memory_get_peak_usage() / 1024) . 'KB';
                    }
                    echo json_encode($json);
                    exit;
                }
            }
        } catch (FwException $e) {
            if (S::$dbm->transaction_flag) {
                // トランザクションを実行中に例外処理が起きた場合、ロールバックする
                S::$dbm->rollback();
            } else if (S::$dbm->lock_flag) {
                // テーブル排他ロック中に例外処理が起きた場合、テーブル排他ロックを解除する
                S::$dbm->unlock();
            }

            if (ENV <= 1 or !preg_match('/notice/', $e->getMessage())) {
                $error = sprintf(
                    '%s(%s) %s',
                    str_replace(SERVER_PATH, '', $e->getFile()),
                    $e->getLine(),
                    $e->getMessage()
                );
                Log::error($error);
            }

            // エラーページの表示
            // テスト環境の場合、デバッグ用のエラーを表示する
            if (!S::$jflag) {
                if ($this->debug and isset($error) and !isset($_SESSION['error_message'])) {
                    $_SESSION['error_message'] = $error;
                }
                if (!$this->error_flag) {
                    $this->error_flag = true; //  循環防止のフラグ
                    $this->disp('error_page', $folder); //  エラー画面モデルの読み込み
                } else {
                    echo '申し訳ございません。しばらく経ってからアクセスしてください。';
                    if ($this->debug) {
                        echo '（同階層のerror_page.phpに問題があった場合、この画面が出ます。）';
                    }
                }
            } else {
                $array = [];
                $array['alert'] = $this->debug ? $error : 'エラー';
                echo json_encode($array);
            }

            // DBセッションを明示的にリセット
            session_write_close();
            S::$dbm = null;
            S::$dbs = null;
            exit;
        } finally {
        }
    }

    /**
     * デバッグ情報の表示
     */
    public function dispDebug()
    {
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

            global $first_memory;
            global $first_time;
            global $dump;
            echo sprintf(
                '<p style="background:#ffcc00;clear:both;">【DB SLAVE】<br />%s</p>'
                , nl2br(htmlspecialchars(S::$dbs->disp_sql))
            );
            echo sprintf(
                '<p style="background:#ff8800;">【DB MASTER】<br />%s</p>'
                , nl2br(htmlspecialchars(S::$dbm->disp_sql))
            );
            echo sprintf(
                '<p style="background:#99aaff;">【MEMCACHED】<br />%s</p>'
                , nl2br(htmlspecialchars(S::$mem->disp_mem))
            );
            echo sprintf(
                '<pre><p style="background:#ffcc66;">【POST】<br />%s</p></pre>'
                , htmlspecialchars($post)
            );
            echo sprintf(
                '<pre><p style="background:#ffcc33;">【GET】<br />%s</p></pre>'
                , htmlspecialchars($get)
            );
            echo sprintf(
                '<pre><p style="background:#ffdd99;">【URL】<br />%s</p></pre>'
                , htmlspecialchars($url)
            );
            echo sprintf(
                '<pre><p style="background:#eecc00;">【FILES】<br />%s</p></pre>'
                , htmlspecialchars($files)
            );
            echo sprintf(
                '<pre><p style="background:#ffff00;">【DUMP】<br />%s</p></pre>'
                , htmlspecialchars($dump)
            );

            $peak_memory = memory_get_peak_usage() / 1024;
            $last_time = microtime(true);
            echo sprintf(
                '<p style="background:#ff0000;">デバッグモード<br />
                  OS: %s PHP ver: %s<br />
                  メモリ使用量: %s KB (固定分) + %s KB (追加分) = %s KB<br />
                  実行時間: %s 秒<br />
                  IP: %s<br />
                  タイムスタンプ: %s (%d)</p>',
                PHP_OS, phpversion(),
                number_format($first_memory),
                number_format($peak_memory - $first_memory),
                number_format($peak_memory),
                round($last_time - $first_time, 3),
                IP_ADDRESS,
                TIMESTAMP, time()
            );
        }
    }  

    /*
     * サニタイズ
     */
    public function h($data)
    {
        if (is_array($data)) {
            foreach ($data as $k => $v) {
                $data[$k] = $this->h($v);
            }
        } else {
            $data = preg_replace('/[\x00-\x09\x0B\x0C\x0E-\x1F\x7F]/', '', $data); //  改行コード以外のコントロールコードを排除
            // UNICODE不可視文字トリム
            $invisible_utf8_codes = array(
                '&#x00AD;',
                '&#x2000;','&#x2001;','&#x2002;','&#x2003;','&#x2004;','&#x2005;','&#x2006;','&#x2007;','&#x2008;','&#x2009;','&#x200A;','&#x200B;','&#x200C;','&#x200D;','&#x200E;','&#x200F;',
                '&#x2028;','&#x2029;','&#x202A;','&#x202B;','&#x202C;','&#x202D;','&#x202E;','&#x202F;',
                '&#x205F;',
                '&#x2060;','&#x2061;','&#x2062;','&#x2063;','&#x2064;','&#x2065;','&#x2066;','&#x2067;','&#x2068;','&#x2069;','&#x206A;','&#x206B;','&#x206C;','&#x206D;','&#x206E;','&#x206F;',
                '&#x2322;','&#x2323;',
                '&#x2800;',
                '&#x3164;',
                '&#xA717;','&#xA718;','&#xA719;','&#xA71A;',
                '&#xA720;','&#xA721;',
                '&#xFE00;','&#xFE01;','&#xFE02;','&#xFE03;','&#xFE04;','&#xFE05;','&#xFE06;','&#xFE07;','&#xFE08;','&#xFE09;','&#xFE0A;','&#xFE0B;','&#xFE0C;','&#xFE0D;','&#xFE0E;','&#xFE0F;',
                '&#xFEFF;',
                '&#xFFF0;','&#xFFF1;','&#xFFF2;','&#xFFF3;','&#xFFF4;','&#xFFF5;','&#xFFF6;','&#xFFF7;','&#xFFF8;',
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
