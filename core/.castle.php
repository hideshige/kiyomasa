<?php
/**
 * オープンソースPHPフレームワーク KIYOMASA
 *
 * @author   Sawada Hideshige
 * @version  1.4.2.3
 * @package  core
 * 
 * 標準コーディング規約
 * http://www.php-fig.org/
 * 
 */

use Php\Framework\Device\Db;
use Php\Framework\Device\Mem;
use Php\Framework\Device\Session;
use Php\Framework\Device\Turret;
use Php\Framework\Device\S;
use Php\Framework\Device\Log;

$first_time = microtime(true);
$first_memory = memory_get_usage() / 1024;

// PHP環境の確認
if (!extension_loaded('mbstring')) {
    echo 'mbstringがインストールされていません';
    exit;
} else if (!extension_loaded('PDO')) {
    echo 'PDOがインストールされていません';
    exit;
}

header("P3P: CP='UNI CUR OUR'"); // コンパクトプライバシーポリシー
header('X-XSS-Protection: 1; mode=block'); // XSS対策
header('Content-Type: text/html;charset=UTF-8');

require_once(__DIR__ . '/.define.php');
require_once(__DIR__ . '/env.php');
require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/../device/.turret.php');
require_once(__DIR__ . '/../device/.tower.php');

new Castle();

class Castle
{
    private $debug; // デバッグモード

    public function __construct()
    {
        try {
            S::$jflag = false;
            $this->debug = ENV <= 1 ? true : false;
            
            // データベースに接続
            $this->dbConnect();
            
            // memchached
            S::$mem = new Mem();
            S::$mem->debug = $this->debug;

            // セッションのセット
            new Session();

            $turret = new Turret();
            $turret->debug = $this->debug;

            // HTMLクエリのセット
            S::$post = $turret->h($_POST);
            S::$get = $turret->h($_GET);
            if (isset(S::$get['url'])) {
                S::$url = explode('/', preg_replace('<^/>', '', S::$get['url']));
                unset(S::$get['url']);
            }
            
            $this->open($turret);
        } catch (\Error $e) {
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
                echo 'エラーになりました。 ' . TIMESTAMP;
            }
            exit;
        }
    }
    
    /**
     * データベースの接続
     */
    private function dbConnect(): void
    {
        S::$dbm = new Db();
        $res_dbm = S::$dbm->connect(
            DB_MASTER_SERVER, DB_MASTER_USER, DB_MASTER_PASSWORD, DB_MASTER_NAME
        );
        if (!$res_dbm) {
            throw new \Error('DB_MASTER Connect Error');
        }
        S::$dbs = new Db();
        $res_dbs = S::$dbs->connect(
            DB_SLAVE_SERVER, DB_SLAVE_USER, DB_SLAVE_PASSWORD, DB_SLAVE_NAME
        );
        if (!$res_dbs) {
            Log::error(
                'DB_SLAVE Connect Error ---> DB_MASTER Connect Change'
            );
            S::$dbs = S::$dbm;
        }
        S::$dbm->debug = $this->debug;
        S::$dbs->debug = $this->debug;
    }
    
    /**
     * 開く
     * @global array $g_folder
     * @param object $turret
     */
    private function open(turret &$turret): void
    {
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
        
        if ($this->mainteCheck() and !$this->debug) {
            $pagename = 'mainte';
            $folder = '';
        }
        $turret->disp($pagename, $folder);
    }
    
    /**
     * メンテナンスモードの判定
     */
    private function mainteCheck(): bool
    {
        return false;
    }
}

/**
 * ダンプをバッファに保存してデバッグに表示する
 * "dump(ダンプしたい変数)"の形で利用する
 * @global string $dump ダンプ用バッファ
 * @param mixed ダンプするデータをカンマ区切りで記入する
 */
$dump = '';
function dump()
{
    global $dump;
    $bt = debug_backtrace();
    $dump .= sprintf(
        "# %s {{DUMP_LINE}}%s\n",
        str_replace(SERVER_PATH, '', $bt[0]['file']),
        $bt[0]['line']
    );
    ob_start();
    foreach ($bt[0]['args'] as $v) {
        var_dump($v);
    }
    $dump .= ob_get_clean();
    return $dump;
}
