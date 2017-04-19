<?php
/**
 * シェルを実行するコントローラークラス
 *
 * @author   Hideshige Sawada
 * @version  1.1.0.0
 * @package  controller
 *
 * ターミナルから以下のように実行する
 * php shells/controller.php サーバー識別 モデル名 パラメーター
 * cron 設定例
 * 00 5 * * * php /var/www/html/yoursite/shells/controller.php 1 sample test 1>> /var/www/html/yoursite/logs/batch/test_$(date +\%y\%m\%d).log 2>&1
 * cronを実行できるように環境にあわせてchdir()の値を変えること
 *
 */

if (intval($argv[1]) == 1) {
  //開発環境
  chdir('/var/www/html/your_site/shells');
} else if (intval($argv[1]) > 1) {
  //本番環境
  chdir('/var/www/html/your_site/shells');
} else {
  echo "argv error\n";
  exit;
}

require_once('../common/env.php');
require_once('../common/define.php');
require_once('../equipment/log.php');
require_once('../equipment/db.php');
require_once('../common/citadel.php');

$controller = new controller;

class controller {
  private $_debug = false;//デバッグモード
  private $_error_flag = false;//初回エラーかどうか（循環防止のため）

  /*
   * オブジェクトの作成
   */
  public function __construct() {
    try {
      global $argv;
      if (!isset($argv[2])) { exit; }
      log::$batch = 'batch/';
      S::$dbm = new db();
      $res_dbm = S::$dbm->connect(DB_MASTER_SERVER, DB_MASTER_USER, DB_MASTER_PASSWORD, DB_MASTER_NAME);
      if (!$res_dbm) { throw new Exception('DB_MASTER Connect Error'); }
      S::$dbs = new db();
      $res_dbs = S::$dbs->connect(DB_SLAVE_SERVER, DB_SLAVE_USER, DB_SLAVE_PASSWORD, DB_SLAVE_NAME);
      if (!$res_dbs) {
        log::error('DB_SLAVE Connect Error ---> DB_MASTER Connect Change');
        $res_dbs2 = S::$dbm->connect(DB_MASTER_SERVER, DB_MASTER_USER, DB_MASTER_PASSWORD, DB_MASTER_NAME);
        if (!$res_dbs2) { throw new Exception('DB_MASTER Connect Error'); }
      }

      S::$dbm->debug = $this->_debug;
      S::$dbs->debug = $this->_debug;
      
      $this->_exec($argv[2]);

      if ($this->_debug) {
        echo "DBS\n";
        echo S::$dbs->disp_sql;
        echo "DBM\n";
        echo S::$dbm->disp_sql;
      }
    } catch (Exception $e) {
      $error = sprintf('%s(%s) %s', str_replace(SERVER_PATH, '', $e->getFile()), $e->getLine(), $e->getMessage());
      log::error($error);
    } finally {
    }
  }


  /*
   * モジュールの組み込み
   * $model モデルのオブジェクト（参照渡し）
   * return bool
   */
  private function _get_equipment(&$model) {
    if (count($model->equipment)) {
      foreach ($model->equipment as $v) {
        if (!include_once(sprintf('../equipment/%s.php', $v))) { return false; }
        $mods[$v] = new $v;
      }
    }
    return true;
  }


  /*
   * モデルを実行する
   * $pagename 実行するモデルの名前
   */
  private function _exec($pagename) {
    try {
      $file = sprintf('%s.php', $pagename);
      if (!file_exists($file) or !include_once($file)) {
        throw new Exception(sprintf('%s read notice', $pagename));
      }

      $model = new $pagename;
      $res_equ = $this->_get_equipment($model);
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
      
      S::$err = $e->getMessage();
      
      $error = sprintf('%s(%s) %s', str_replace(SERVER_PATH, '', $e->getFile()), $e->getLine(), $e->getMessage());
      log::error($error);
      
      if (!$this->_error_flag) {
        $this->_error_flag = true;//循環防止のフラグ
        $this->_exec('error');//エラー画面モデルの読み込み
      } else {
        echo 'KIYOMASA ERROR';
        exit;
      }
    } finally {
    }
  }
}

/**
 * パラメータのショートカット用スタティックオブジェクト
 *
 */
class S {
  static $dbm;//DBマスターモジュール
  static $dbs;//DBスレーブモジュール
}
