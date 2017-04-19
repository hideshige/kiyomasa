<?php
/**
 * memcached モジュール
 *
 * @author   Hideshige Sawada
 * @version  1.0.4.0
 * @package  module
 * 
 * バックアップ用テーブルを準備しておく
 CREATE TABLE `memcached` (
  `memcached_key` VARCHAR(255) NOT NULL,
  `memcached_value` TEXT,
  `temp_flag` TINYINT NOT NULL DEFAULT 0,
  `expire` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`memcached_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
 *
 */

class memcached_module {
  private $_memcached1;//memcachedオブジェクト
  private $_active;//memcachedが起動しているかどうかのフラグ
  public $disp_mem = '';//debug情報
  public $debug;//debugかどうかのフラグ

  /**
   * 接続
   */
  public function __construct() {
//    $this->_memcached1 = new Memcache();
    $this->_memcached1 = new Memcached();

    //主がNGの場合は副を使用
//    $this->_active = @$this->_memcached1->connect(MEMCACHED_SERVER, 11211);
    $this->_active = @$this->_memcached1->addServer(MEMCACHED_SERVER, 11211);
    if (!$this->_active) {
      log::error('memcached down');
    }
  }

  /**
   * memcached に保存する
   * @param string $key キー
   * @param string or array $var　値
   * @param boolean $comp 圧縮の有無
   * @param datetime $expire 有効期限
   */
  public function set($key, $var, $comp = false, $expire = 0) {
    if ($this->debug and $this->_active) {
      $bt = debug_backtrace();
      $dump = sprintf("%s(%s)", $bt[0]['file'], $bt[0]['line']);
      $this->disp_mem .= sprintf("■SET %s\n[K]%s [V]%s\n", $dump, $key, print_r($var, true));
    }
    if ($this->_active) {
      //$res = $this->_memcached1->set($key, $var, $comp, $expire);
      $res = $this->_memcached1->set($key, $var, $expire);
    }

    //memcachedが有効でない場合か有効期限の指定がない場合、データベースに値を保存
    if (!$this->_active or !$expire) {
      $temp_flag = $expire ? 1 : 0;
      $params = array ();
      $params['memcached_key'] = $key;
      $params['memcached_value'] = serialize($var);
      $params['temp_flag'] = $temp_flag;
      $params['expire'] = TIMESTAMP;
      $params['created_at'] = TIMESTAMP;
      S::$dbm->insert('memcached', $params, true);
      $res = S::$dbm->bind($params);
    }
    return $res;
  }

  /**
   * memcach から値を取得する
   * @param string $key キー
   */
  public function get($key) {
    $var = @$this->_memcached1->get($key);
    if ($var === false) {
      //データベースから値を取得
      $param = array ($key);
      $where = 'WHERE memcached_key = ?';
      S::$dbs->select('memcached', '*', $where);
      $res = S::$dbs->bind_select($param);
      if (!$res) return false;

      $var = unserialize($res[0]['memcached_value']);
      $expire = $res[0]['temp_flag'] ? MEMCACHED_LIMIT_TIME : 0;

      if ($this->_active) {
        //データベースの値をmemcachedに保存
        @$this->_memcached1->set($key, $var, false, $expire);
      }
    }
    if ($this->debug and $this->_active) {
      $bt = debug_backtrace();
      $dump = sprintf("%s (%s)", $bt[0]['file'], $bt[0]['line']);
      $this->disp_mem .= sprintf("■GET %s\n[K]%s [V]%s\n", $dump, $key, print_r($var, true));
    }
    return $var;
  }

  /**
   * memcach から値を削除する
   * @param string $key キー
   */
  public function delete($key) {
    $res = $this->_memcached1->delete($key);
    
    $param = array ($key);
    $where = 'WHERE memcached_key = ?';
    S::$dbm->delete('memcached', $where);
    S::$dbm->bind($param);
    
    if ($this->debug and $this->_active) {
      $bt = debug_backtrace();
      $dump = sprintf("%s (%s)", $bt[0]['file'], $bt[0]['line']);
      $this->disp_mem .= sprintf("■DELETE %s\n[K]%s", $dump, $key);
    }
    return $res;
  }
}

