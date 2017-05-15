<?php
/**
 * シェルを実行する
 *
 * @author   Hideshige Sawada
 * @version  1.1.3.0
 * @package  shells
 *
 * ターミナルから以下のように実行する
 * php shells/controller.php モデル名 パラメーター
 * cron 設定例
 * 00 5 * * * php /var/www/html/yoursite/shells/camp.php sample test 1>> /var/www/html/yoursite/logs/batch/test_$(date +\%y\%m\%d).log 2>&1
 * cronを実行できるように環境にあわせてchdir()の値を変えること
 *
 */

namespace kiyomasa;

use Exception;

require_once(__DIR__ . '/../conf/env.php');
require_once(__DIR__ . '/../conf/define.php');
require_once(__DIR__ . '/../device/log.php');
require_once(__DIR__ . '/../device/db.php');
require_once(__DIR__ . '/../device/turret.php');
require_once(__DIR__ . '/../device/wall.php');

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
                Log::error('DB_SLAVE Connect Error ---> DB_MASTER Connect Change');
                $res_dbs2 = S::$dbm->connect(
                    DB_MASTER_SERVER,
                    DB_MASTER_USER,
                    DB_MASTER_PASSWORD,
                    DB_MASTER_NAME
                );
                if (!$res_dbs2) {
                    throw new FwException('DB_MASTER Connect Error');
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
        } catch (FwException $e) {
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
            $file = __DIR__ . sprintf('/%s.php', $pagename);
            if (!file_exists($file) or !require_once($file)) {
                throw new FwException(sprintf('%s read notice', $pagename));
            }

            $turret = new Turrets();
            $turret->debug = $this->debug;
            
            $class_name = __NAMESPACE__ . '\\' . className($pagename);
            $model = new $class_name();
            $res_equ = $turret->getEquipment($model);
            if (!$res_equ) {
                throw new FwException($pagename . ' equipment read notice');
            }
            $res = $model->logic();
            if ($res === false) {
                throw new FwException($pagename . ' logic notice');
            }
        } catch (FwException $e) {
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
            throw new FwException($error);
        } finally {
        }
    }
}

class FwException extends Exception
{
}
