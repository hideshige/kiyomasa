<?php
/**
 * キャッスル　コントローラ
 *
 * @author   Sawada Hideshige
 * @version  1.4.4.1
 * @package  core
 * 
 */

namespace Php\Framework\Core;

use Php\Framework\Device\Db\DbModule;
use Php\Framework\Device\Mem;
use Php\Framework\Device\Session;
use Php\Framework\Device\S;
use Php\Framework\Device\Log;

require_once(__DIR__ . '/.tower.php');
require_once(__DIR__ . '/.wall.php');
require_once(__DIR__ . '/.turret.php');

class Castle
{
    private $debug; // デバッグモード

    /**
     * コンストラクタ
     */
    public function __construct()
    {
        try {
            S::$jflag = false;
            $this->debug = ENV <= 1 ? true : false;
            
            // データベースオブジェクトの準備
            S::$dbm = new DbModule(DB_MASTER_SERVER, DB_MASTER_USER,
                DB_MASTER_PASSWORD, DB_MASTER_NAME, DB_SOFT, $this->debug);
            S::$dbs = new DbModule(DB_SLAVE_SERVER, DB_SLAVE_USER,
                DB_SLAVE_PASSWORD, DB_SLAVE_NAME, DB_SOFT, $this->debug);
            if (!S::$dbs->connect()) {
                // スレーブが使えない場合、マスターを使う
                S::$dbs = S::$dbm;
            }
            
            // memchached
            S::$mem = new Mem($this->debug);

            // セッションのセット
            new Session();

            $turret = new Turret($this->debug);

            // HTMLクエリのセット
            S::$post = $turret->h($_POST);
            S::$get = $turret->h($_GET);
            
            $this->open($turret);
        } catch (\Error $e) {
            $this->error($e);
        }
    }
    
    /**
     * エラー処理
     * @param \Error $e
     * @return void
     */
    private function error(\Error $e): void
    {
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
    
    /**
     * 開く
     * @global array $g_folder
     * @param object $turret
     * @return void
     */
    private function open(turret &$turret): void
    {
        // URLの指定がなければトップページを指定
        $folder = '';
        $pagename = '';
        S::$url['folder'] = '';
        if (isset(S::$get['url'])) {
            list($pagename, $folder) = $this->setPagename();
        }
        if ($pagename === '') {
            $pagename = 'index';
        }
        if ($this->mainteCheck() and $this->debug === false) {
            $pagename = 'mainte';
            $folder = '';
        }
        $turret->disp($pagename, $folder);
    }
    
    /**
     * ページ名のセット
     * @return array
     */
    private function setPagename(): array
    {
        $folder = '';
        global $g_folder;
        if ($g_folder) {
            foreach ($g_folder as $v) {
                if (preg_match('<^/' . $v . '>', S::$get['url'])) {
                    $folder = $v . '/';
                    break;
                }
            }
        }
        S::$url['folder'] = $folder;
        $url = explode('/',
            preg_replace('<^/' . $folder . '>', '', S::$get['url']));
        unset(S::$get['url']);
        S::$url += $url;
        $pagename = (isset($url[0]) and $url[0] !== '') ? $url[0] : '';
        return [$pagename, $folder];
    }
    
    /**
     * メンテナンスモードの判定
     * @return bool
     */
    private function mainteCheck(): bool
    {
        return false;
    }
}
