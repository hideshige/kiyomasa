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

require_once(__DIR__ . '/../conf/env.php');
require_once(__DIR__ . '/../conf/define.php');
require_once(__DIR__ . '/../extension/log.php');
require_once(__DIR__ . '/../extension/db.php');
require_once(__DIR__ . '/../extension/stone_walls.php');
require_once(__DIR__ . '/../common/citadel.php');

new Camp;

class Camp
{
    private $debug = false; // デバッグモード
    private $error_flag = false; // 初回エラーかどうか（循環防止のため）

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
                throw new Exception('DB_MASTER Connect Error');
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
                    throw new Exception('DB_MASTER Connect Error');
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
        } catch (Exception $e) {
            $error = sprintf(
                '%s(%s) %s',
                str_replace(SERVER_PATH, '', $e->getFile()),
                $e->getLine(),
                $e->getMessage()
            );
            Log::error($error);
        } finally {
        }
    }

    /**
     * モジュールの組み込み
     * @param object $model モデルのオブジェクト（参照渡し）
     * @return bool
     */
    private function getEquipment(&$model)
    {
        $res = false;
        if (count($model->equipment)) {
            foreach ($model->equipment as $v) {
                if (include_once(sprintf('../equipment/%s.php', $v))) {
                    $class_name = __NAMESPACE__ . '\\' . className($v);
                    new $class_name;
                    $res = true;
                }
            }
        }
        return $res;
    }


    /**
     * モデルを実行する
     * @param string $pagename 実行するモデルの名前
     */
    private function exec($pagename)
    {
        try {
            $file = sprintf('%s.php', $pagename);
            if (!file_exists($file) or !include_once($file)) {
                throw new Exception(sprintf('%s read notice', $pagename));
            }

            $model = new $pagename;
            $res_equ = $this->getEquipment($model);
            if (!$res_equ) {
                throw new Exception($pagename . ' equipment read notice');
            }
            $res = $model->logic();
            if ($res === false) {
                throw new Exception($pagename . ' logic notice');
            }
        } catch (Exception $e) {
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
            Log::error($error);

            if (!$this->error_flag) {
                $this->error_flag = true;//循環防止のフラグ
                $this->exec('error');//エラー画面モデルの読み込み
            } else {
                echo 'KIYOMASA ERROR';
                exit;
            }
        } finally {
        }
    }
}
