<?php
/**
 * キャンプ　シェル土台部
 *
 * @author   Sawada Hideshige
 * @version  1.1.8.2
 * @package  core
 *
 * ターミナルから以下のように実行する
 * php core/.camp.php ゲート名 パラメーター
 * cron 設定例
 * 00 5 * * * php /var/www/html/yoursite/core/.camp.php sample test 1>> /var/www/html/yoursite/log/batch/test_$(date +\%y\%m\%d).log 2>&1
 *
 */

namespace Php\Framework\Core;

use Php\Framework\Device\{S, Log};

require_once(__DIR__ . '/.define.php');
require_once(__DIR__ . '/env.php');
require_once(__DIR__ . '/mode.php');
require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/.tower.php');

new Camp;

class Camp
{
    /**
     * コンストラクタ
     * @global array $argv
     */
    public function __construct()
    {
        try {
            global $argv;
            if (!isset($argv[1])) {
                exit(0);
            }
            Log::$batch = 'batch_';
            
            $debug = ENV <= ENV_DEV ? true : false;
            
            // データベースオブジェクトの準備
            $dbo = $debug ?
                'Php\Framework\Device\DebugDb' : 'Php\Framework\Device\Db';
            S::$dbm = new $dbo(DB_MASTER_SERVER, DB_MASTER_USER,
                DB_MASTER_PASSWORD, DB_NAME, DB_DRIVER);
            S::$dbs = new $dbo(DB_SLAVE_SERVER, DB_SLAVE_USER,
                DB_SLAVE_PASSWORD, DB_NAME, DB_DRIVER);
            
            if (DB_MASTER_SERVER !== DB_SLAVE_SERVER and !S::$dbs->connect()) {
                // スレーブが使えない場合、マスターを使う
                S::$dbs = clone S::$dbm;
                S::$dbm->connectCheck();
            }
            
            // memchached
            $mem = $debug ?
                'Php\Framework\Device\DebugMem' : 'Php\Framework\Device\Mem';
            S::$mem = new $mem;
           
            $this->exec($argv[1]);

            if ($debug) {
                echo '<DBS>', PHP_EOL, S::$dbs->getSql(), PHP_EOL;
                echo '<DBM>', PHP_EOL, S::$dbm->getSql(), PHP_EOL;
            }
        } catch (\Error|\PDOException $e) {
            echo $e->getMessage();
        }
    }

    /**
     * 実行する
     * @param string $pagename 実行するページ名
     * @return void
     * @throws \Error
     */
    private function exec(string $pagename): void
    {
        try {
            $class_name = NAME_SPACE . '\Shell\\' . trim(str_replace(
                ' ', '', ucwords(str_replace('_', ' ', $pagename))));
            
            $gate = new $class_name();
            $res = $gate->execute();
            if ($res === false) {
                throw new \Error($pagename . " logic notice\n", 10);
            }
        } catch (\Error $e) {
            S::$dbm->rollback();
            $error = sprintf('%s(%s) %s',
                str_replace(SERVER_PATH, '', $e->getFile()),
                $e->getLine(), $e->getMessage());
            throw new \Error($error);
        }
    }
}
