<?php
/**
 * $_SESSION変数を使ってDBに保存可能にするセッションモジュール
 *
 * @author   Hideshige Sawada
 * @version  1.1.2.5
 * @package  equipment
 * 
 * セッションの保存方法は3種類から選べる
 * (1)memcachedに保存する → session_handler_memを使用すること
 * (2)データベースに保存する → session_handlerを使用すること
 * (3)ファイルをsessionディレクトリに保存する → ハンドラを使用しないこと
 * 
 * セッションの読み方
 * $a = $_SESSION['example'];
 * 
 * セッションの書き方
 * $_SESSION['example'] = $a;//配列でも可
 * デバッグに書き込みしたことを表示させる場合は、コントローラで明示的に
 * session_write_close();
 * を実行させること
 * 
 * セッションの消し方
 * setcookie('login_session_id', '', - time() - 60*60*24*365, '/');//COOKIEを消す
 * session_destroy();//DBのレコードを消す
 * 
 */

class session {
  
  public function __construct() {
    $handler = new session_handler_mem();
    //$handler = new session_handler();
    session_set_save_handler(
      array ($handler, 'open'),
      array ($handler, 'close'),
      array ($handler, 'read'),
      array ($handler, 'write'),
      array ($handler, 'destroy'),
      array ($handler, 'gc')
   );
    ini_set('session.gc_maxlifetime', COOKIE_LIFETIME);
    ini_set('session.cookie_httponly', 1);
    session_save_path(SERVER_PATH . 'session');
    session_name('login');
    session_set_cookie_params(COOKIE_LIFETIME, '/', '', false, true);
    session_start();
  }
  
  /**
   * セッションIDの値を変えてCOOKIEを更新
   */
  public static function session_id_change() {
    session_regenerate_id();
  }
}

/**
 * セッションハンドラのカスタマイズ(DBに保存する場合)
 * 
 * ※DBに以下のテーブルを事前に用意する
 * 
    CREATE TABLE `t_session` (
     `session_id` CHAR(32) NOT NULL,
     `value` TEXT,
     `expires` INT(11) DEFAULT NULL,
     `created_at` DATETIME,
     `updated_at` DATETIME,
     PRIMARY KEY  (`session_id`)
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8
 * 
 */
class session_handler {
  
  function open($save_path, $session_name) {
    return true;
  }
  
  function close() {
    return true;
  }
  
  function read($ses_id) {
    $params = array ($ses_id, time());
    S::$dbm->select('t_session', 'value', 'WHERE session_id = ? AND expires > ?');
    $res = S::$dbm->bind_select($params);
    if (!$res) return '';
    return $res[0]['value'];
  }
  
  function write($ses_id, $data) {
    $params = array ();
    $params['session_id'] = $ses_id;
    $params['value'] = $data;
    $params['expires'] = time() + COOKIE_LIFETIME;
    S::$dbm->insert('t_session', $params, true);
    S::$dbm->bind($params);
    return true;
  }
  
  function destroy($ses_id) {
    $params = array ($ses_id);
    S::$dbm->delete('t_session', 'WHERE session_id = ?');
    S::$dbm->bind($params);
    return true;
  }
  
  function gc($ses_time) {
    $params = array (time());
    S::$dbm->delete('t_session', 'WHERE expires < ?');
    S::$dbm->bind($params);
    return true;
  }
}

/**
 * セッションハンドラのカスタマイズ(memcachedに保存する場合)
 */
class session_handler_mem {
  
  function open($save_path, $session_name) {
    return true;
  }
  
  function close() {
    return true;
  }
  
  function read($ses_id) {
    $res = S::$mem->get($ses_id);
    if (!$res) return '';
    return $res;
  }
  
  function write($ses_id, $data) {
    S::$mem->set($ses_id, $data, false, MEMCACHED_LIMIT_TIME);
    return true;
  }
  
  function destroy($ses_id) {
    S::$mem->delete($ses_id);
    return true;
  }
  
  function gc($ses_time) {
    return true;
  }
}
