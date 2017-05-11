<?php
/**
 * $_SESSION変数を使ってDBに保存可能にするセッションモジュール
 *
 * @author   Hideshige Sawada
 * @version  1.1.4.0
 * @package  extension
 * 
 * セッションの保存方法は3種類から選べる
 * (1)memcachedに保存する → sessionHandlerMemを使用すること
 * (2)データベースに保存する → sessionHandlerDbを使用すること
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
 * setcookie(PROJECT_PREFIX.'login', '', time() - COOKIE_LIFETIME, '/');//COOKIEを消す
 * session_destroy();//DBのレコードを消す
 * 
 */

namespace kiyomasa;

class Session
{
    public function __construct()
    {
        $handler = new sessionHandlerMem();
        //$handler = new sessionHandlerDb();
        session_set_save_handler(
            [$handler, 'open'],
            [$handler, 'close'],
            [$handler, 'read'],
            [$handler, 'write'],
            [$handler, 'destroy'],
            [$handler, 'gc']
        );
        ini_set('session.gc_maxlifetime', COOKIE_LIFETIME);
        ini_set('session.cookie_lifetime', COOKIE_LIFETIME);
        ini_set('session.cookie_httponly', 1);
        session_save_path(SERVER_PATH . 'session');
        session_name(PROJECT_PREFIX . 'login');
        session_set_cookie_params(COOKIE_LIFETIME, '/', '', false, true);
        session_start();
    }

    /**
     * セッションIDの値を変えてCOOKIEを更新
     */
    public static function sessionIdChange()
    {
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
class sessionHandlerDb
{

    function open($save_path, $session_name)
    {
        return true;
    }

    function close()
    {
        return true;
    }

    function read($ses_id)
    {
        $params = array ($ses_id, time());
        S::$dbm->select('t_session', 'value', 'WHERE session_id = ? AND expires > ?');
        $res = S::$dbm->bindSelect($params);
        if (!$res) { return ''; }
        return $res[0]['value'];
    }

    function write($ses_id, $data)
    {
        $params = [];
        $params['session_id'] = $ses_id;
        $params['value'] = $data;
        $params['expires'] = time() + COOKIE_LIFETIME;
        S::$dbm->insert('t_session', $params, true);
        S::$dbm->bind($params);
        return true;
    }

    function destroy($ses_id)
    {
        $params = array ($ses_id);
        S::$dbm->delete('t_session', 'WHERE session_id = ?');
        S::$dbm->bind($params);
        return true;
    }

    function gc($ses_time)
    {
        $params = array (time());
        S::$dbm->delete('t_session', 'WHERE expires < ?');
        S::$dbm->bind($params);
        return true;
    }
}

/**
 * セッションハンドラのカスタマイズ(memcachedに保存する場合)
 */
class sessionHandlerMem
{
    function open($save_path, $session_name)
    {
        return true;
    }

    function close()
    {
        return true;
    }

    function read($ses_id)
    {
        $res = S::$mem->get($ses_id);
        if (!$res) {
            $res = '';
        }
        return $res;
    }

    function write($ses_id, $data)
    {
        S::$mem->set($ses_id, $data, false, time() + COOKIE_LIFETIME);
        return true;
    }

    function destroy($ses_id)
    {
        S::$mem->delete($ses_id);
        return true;
    }

    function gc($ses_time)
    {
        return true;
    }
}
