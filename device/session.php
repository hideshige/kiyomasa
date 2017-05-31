<?php
/**
 * $_SESSION変数を使ってDBに保存可能にするセッションモジュール
 *
 * @author   Sawada Hideshige
 * @version  1.1.4.3
 * @package  device
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

namespace Php\Framework\Device;

class Session
{
    public function __construct()
    {
        $handler = new sessionHandlerMem();
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
        session_name(PROJECT_PREFIX . 'login_sesid');
        session_set_cookie_params(COOKIE_LIFETIME, '/', '', false, true);
        session_start();
    }

    /**
     * セッションIDの値を変えてCOOKIEを更新
     */
    public static function sessionIdChange(): void
    {
        session_regenerate_id();
    }
}

/**
 * セッションハンドラのカスタマイズ(DBに保存する場合)
 * 
 * ※DBに以下のテーブルを事前に用意する
 * 
    CREATE TABLE t_session (
        session_id CHAR(32) NOT NULL,
        session_value TEXT,
        session_expires INT(11) DEFAULT NULL,
        created_at DATETIME,
        updated_at DATETIME,
        PRIMARY KEY  (`session_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8
 * 
 */
class sessionHandlerDb
{
    /**
     * 開く
     * @param string $save_path
     * @param string $session_name
     * @return bool
     */
    public function open(string $save_path, string $session_name): bool
    {
        return true;
    }

    /**
     * 閉じる
     * @return bool
     */
    public function close(): bool
    {
        return true;
    }

    /**
     * 読み込み
     * @param string $ses_id
     * @return string
     */
    public function read(string $ses_id): string
    {
        $read = '';
        $params = [];
        $params['session_id'] = $ses_id;
        $params['session_expires'] = time();
        S::$dbm->select(
            't_session',
            'session_value',
            'WHERE session_id = :session_id AND '
            . 'session_expires > :session_expires',
            'session'
        );
        S::$dbm->bind($params, 'session');
        $res = S::$dbm->Fetch('stdClass', 'session');
        if ($res) {
            $read = $res->session_value;
        }
        return $read;
    }

    /**
     * 書き込み
     * @param string $ses_id
     * @param string $data
     * @return bool
     */
    public function write(string $ses_id, string $data): bool
    {
        $params = [];
        $params['session_id'] = $ses_id;
        $params['session_value'] = $data;
        $params['session_expires'] = time() + COOKIE_LIFETIME;
        S::$dbm->insert('t_session', $params, true, 'session');
        S::$dbm->bind($params, 'session');
        return true;
    }

    /**
     * 削除
     * @param string $ses_id
     * @return bool
     */
    public function destroy(string $ses_id): bool
    {
        $params = [];
        $params['session_id'] = $ses_id;
        S::$dbm->delete(
            't_session',
            'WHERE session_id = :session_id',
            'session'
        );
        S::$dbm->bind($params, 'session');
        return true;
    }

    /**
     * 有効期限が切れているものを一括削除
     * @param int $ses_time
     * @return bool
     */
    public function gc($ses_time): bool
    {
        $params = [];
        $params['session_expires'] = time();
        S::$dbm->delete(
            't_session',
            'WHERE session_expires < :session_expires',
            'session'
        );
        S::$dbm->bind($params, 'session');
        return true;
    }
}

/**
 * セッションハンドラのカスタマイズ(memcachedに保存する場合)
 */
class sessionHandlerMem
{
    /**
     * 開く
     * @param string $save_path
     * @param string $session_name
     * @return bool
     */
    public function open(string $save_path, string $session_name): bool
    {
        return true;
    }

    /**
     * 閉じる
     * @return bool
     */
    public function close(): bool
    {
        return true;
    }

    /**
     * 読み込み
     * @param string $ses_id
     * @return string
     */
    public function read(string $ses_id): string
    {
        $res = S::$mem->get($ses_id);
        if (!$res) {
            $res = '';
        }
        return $res;
    }

    /**
     * 書き込み
     * @param string $ses_id
     * @param string $data
     * @return bool
     */
    public function write(string $ses_id, string $data): bool
    {
        S::$mem->set($ses_id, $data, time() + COOKIE_LIFETIME);
        return true;
    }

    /**
     * 削除
     * @param string $ses_id
     * @return bool
     */
    public function destroy(string $ses_id): bool
    {
        S::$mem->delete($ses_id);
        return true;
    }

    /**
     * 有効期限が切れているものを一括削除
     * @param type $ses_time
     * @return bool
     */
    public function gc($ses_time): bool
    {
        return true;
    }
}
