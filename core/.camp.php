<?php
/**
 * キャンプ　シェルコントローラ
 *
 * @author   Sawada Hideshige
 * @version  1.1.5.0
 * @package  core
 *
 * ターミナルから以下のように実行する
 * php core/.camp.php モデル名 パラメーター
 * cron 設定例
 * 00 5 * * * php /var/www/html/yoursite/core/.camp.php sample test 1>> /var/www/html/yoursite/log/batch/test_$(date +\%y\%m\%d).log 2>&1
 *
 */

namespace Php\Framework\Core;

use Php\Framework\Device\Db\DbModule;
use Php\Framework\Device\S;
use Php\Framework\Device\Log;

require_once(__DIR__ . '/.define.php');
require_once(__DIR__ . '/env.php');
require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/.tower.php');

new Camp;

class Camp
{
    private $debug = false; // デバッグモード

    /**
     * コンストラクタ
     * @global array $argv
     */
    public function __construct()
    {
        try {
            global $argv;
            if (!isset($argv[1])) {
                exit;
            }
            Log::$batch = 'batch/';
            
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
            
            $this->exec($argv[1]);

            if ($this->debug) {
                echo "<DBS>\n" . S::$dbs->getSql(false) . "\n";
                echo "<DBM>\n" . S::$dbm->getSql(false) . "\n";
            }
        } catch (\Error $e) {
            echo $e->getMessage();
        }
    }

    /**
     * モデルを実行する
     * @param string $pagename 実行するモデルの名前
     * @return void
     * @throws \Error
     */
    private function exec(string $pagename): void
    {
        try {
            $class_name = NAME_SPACE . '\Shell\\' . trim(str_replace(
                ' ', '', ucwords(str_replace('_', ' ', $pagename))));
            
            $model = new $class_name();
            $res = $model->logic();
            if ($res === false) {
                throw new \Error($pagename . ' logic notice', 10);
            }
        } catch (\Error $e) {
            S::$dbm->rollback();
            S::$dbm->unlock();
            $error = sprintf(
                '%s(%s) %s',
                str_replace(SERVER_PATH, '', $e->getFile()),
                $e->getLine(),
                $e->getMessage()
            );
            throw new \Error($error);
        }
    }
}
