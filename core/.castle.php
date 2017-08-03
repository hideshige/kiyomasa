<?php
/**
 * キャッスル　コントローラ
 *
 * @author   Sawada Hideshige
 * @version  1.4.3.3
 * @package  core
 * 
 */

namespace Php\Framework\Core;

use Php\Framework\Device\Db\DbSet;
use Php\Framework\Device\Mem;
use Php\Framework\Device\Session;
use Php\Framework\Device\Turret;
use Php\Framework\Device\S;
use Php\Framework\Device\Log;

require_once(__DIR__ . '/../device/.rampart.php');
require_once(__DIR__ . '/../device/.turret.php');
require_once(__DIR__ . '/../device/.tower.php');

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
            
            // データベースに接続
            $db_set = new DbSet();
            $db_set->dbConnect($this->debug);
            
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
