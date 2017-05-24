<?php
/**
 * シェルを実行する
 *
 * @author   Sawada Hideshige
 * @version  1.1.4.4
 * @package  core
 *
 * ターミナルから以下のように実行する
 * php core/.camp.php モデル名 パラメーター
 * cron 設定例
 * 00 5 * * * php /var/www/html/yoursite/core/.camp.php sample test 1>> /var/www/html/yoursite/log/batch/test_$(date +\%y\%m\%d).log 2>&1
 * cronを実行できるように環境にあわせてchdir()の値を変えること
 *
 */

use Php\Framework\Device\Db;
use Php\Framework\Device\S;
use Php\Framework\Device\Log;
use \Error;

require_once(__DIR__ . '/.define.php');
require_once(__DIR__ . '/env.php');
require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/../device/.tower.php');

new Camp;

class Camp
{
    private $debug = false; // デバッグモード

    public function __construct()
    {
        try {
            global $argv;
            if (!isset($argv[1])) {
                exit;
            }
            Log::$batch = 'batch/';
            S::$dbm = new Db();
            $res_dbm = S::$dbm->connect(
                DB_MASTER_SERVER,
                DB_MASTER_USER,
                DB_MASTER_PASSWORD,
                DB_MASTER_NAME
            );
            if (!$res_dbm) {
                throw new Error('DB_MASTER Connect Error');
            }
            S::$dbs = new Db();
            $res_dbs = S::$dbs->connect(
                DB_SLAVE_SERVER,
                DB_SLAVE_USER,
                DB_SLAVE_PASSWORD,
                DB_SLAVE_NAME
            );
            if (!$res_dbs) {
                Log::error('DB_SLAVE Connect Error ---> DB_MASTER Connect Change');
                $res_dbs2 = S::$dbm->connect(
                    DB_MASTER_SERVER,
                    DB_MASTER_USER,
                    DB_MASTER_PASSWORD,
                    DB_MASTER_NAME
                );
                if (!$res_dbs2) {
                    throw new Error('DB_MASTER Connect Error');
                }
            }

            S::$dbm->debug = $this->debug;
            S::$dbs->debug = $this->debug;
            
            $this->exec($argv[1]);

            if ($this->debug) {
                echo "DBS\n";
                echo S::$dbs->disp_sql;
                echo "DBM\n";
                echo S::$dbm->disp_sql;
            }
        } catch (SystemError $e) {
            Log::error($e->getMessage());
            echo "KIYOMASA ERROR\n";
            exit;
        } finally {
        }
    }

    /**
     * モデルを実行する
     * @param string $pagename 実行するモデルの名前
     */
    private function exec($pagename)
    {
        try {
            $class_name = NAME_SPACE . '\Shell\\' . trim(
                str_replace(' ', '', ucwords(str_replace('_', ' ', $pagename)))
            );

            $model = new $class_name();
            $res = $model->logic();
            if ($res === false) {
                throw new Error($pagename . ' logic notice');
            }
        } catch (SystemError $e) {
            if (S::$dbm->transaction_flag) {
                //トランザクションを実行中に例外処理が起きた場合、ロールバックする
                S::$dbm->rollback();
            } else if (S::$dbm->lock_flag) {
                //テーブル排他ロック中に例外処理が起きた場合、テーブル排他ロックを解除する
                S::$dbm->unlock();
            }
            $error = sprintf(
                '%s(%s) %s',
                str_replace(SERVER_PATH, '', $e->getFile()),
                $e->getLine(),
                $e->getMessage()
            );
            throw new Error($error);
        } finally {
        }
    }
}
