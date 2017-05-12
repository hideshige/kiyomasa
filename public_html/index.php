<?php
/**
 * KIYOMASAフレームワーク
 *
 * @author   Hideshige Sawada
 * @version  1.4.0.0
 * @package  public_html
 * 
 * PHPフレームワーク展示会グループによるコーディング規約に準拠する
 * http:// www.php-fig.org/
 * 
 */

namespace kiyomasa;

use Exception;

header("P3P: CP='UNI CUR OUR'"); // コンパクトプライバシーポリシー
header('X-XSS-Protection: 1; mode=block'); // XSS対策
header('Content-Type: text/html;charset=UTF-8');
$first_time = microtime(true);
$first_memory = memory_get_usage() / 1024;

require_once(__DIR__ . '/../conf/env.php');
require_once(__DIR__ . '/../conf/define.php');
require_once(__DIR__ . '/../device/log.php');
require_once(__DIR__ . '/../device/view.php');
require_once(__DIR__ . '/../device/db.php');
require_once(__DIR__ . '/../device/memcached.php');
require_once(__DIR__ . '/../device/session.php');
require_once(__DIR__ . '/../device/turrets.php');
require_once(__DIR__ . '/../device/stone_walls.php');
require_once(__DIR__ . '/../extension/citadel.php');

new Castle();

class Castle
{
    private $mainte; // メンテナンスモード
    private $debug; // デバッグモード

    public function __construct()
    {
        try {
            // データベースに接続
            S::$jflag = false;
            S::$dbm = new DbModule();
            $res_dbm = S::$dbm->connect(
                DB_MASTER_SERVER,
                DB_MASTER_USER,
                DB_MASTER_PASSWORD,
                DB_MASTER_NAME
            );
            if (!$res_dbm) {
                throw new FwException('DB_MASTER Connect Error');
            }
            S::$dbs = new DbModule();
            $res_dbs = S::$dbs->connect(
                DB_SLAVE_SERVER,
                DB_SLAVE_USER,
                DB_SLAVE_PASSWORD,
                DB_SLAVE_NAME
            );
            if (!$res_dbs) {
                Log::error(
                    'DB_SLAVE Connect Error ---> DB_MASTER Connect Change'
                );
                $res_dbs2 = S::$dbs->connect(
                    DB_MASTER_SERVER,
                    DB_MASTER_USER,
                    DB_MASTER_PASSWORD,
                    DB_MASTER_NAME
                );
                if (!$res_dbs2) {
                    throw new FwException('DB_MASTER Connect Error');
                }
            }

            S::$mem = new MemcachedModule();

            $this->debug = false;
            if (ENV <= 1) {
                $this->debug = true;
            }
            S::$dbm->debug = $this->debug;
            S::$dbs->debug = $this->debug;
            S::$mem->debug = $this->debug;

            $turrets = new Turrets();
            $turrets->debug = $this->debug;
            
            S::$post = $turrets->h($_POST);
            S::$get = $turrets->h($_GET);
            if (isset(S::$get['url'])) {
                S::$url = explode('/', preg_replace('<^/>', '', S::$get['url']));
                unset(S::$get['url']);
            }

            // セッションのセット
            new Session();

            // URLの指定がなければトップページを指定
            $folder = '';
            $url_num = 0;
            global $g_folder;
            if ($g_folder) {
              foreach ($g_folder as $v) {
                if (S::$url[0] == $v) {
                    $folder = $v . '/';
                    $url_num = 1;
                    break;
                }
              }
            }
            if (isset(S::$url[$url_num]) and S::$url[$url_num]) {
                $pagename = S::$url[$url_num];
            } else {
                $pagename = 'index';
            }

            // メンテナンスモードの判定
            $this->mainte = 0;

            if ($this->mainte and !$this->debug) {
                $pagename = 'mainte';
                $folder = '';
            }
            
            $turrets->disp($pagename, $folder);
        } catch (FwException $e) {
            $error = sprintf(
                '%s(%s) %s',
                str_replace(SERVER_PATH, '', $e->getFile()),
                $e->getLine(),
                $e->getMessage()
            );
            Log::error($error);
            // テスト環境の場合、デバッグ用のエラーを表示する
            if ($this->debug) {
                echo $error;
            } else {
                echo 'エラーになりました。 '.TIMESTAMP;
            }
            exit;
        } finally {
        }
    }
}

class FwException extends Exception
{
}