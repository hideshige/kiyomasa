<?php
/**
 * $_SESSION変数を使ってDBに保存可能にするセッションモジュール
 *
 * @author   Sawada Hideshige
 * @version  1.2.1.0
 * @package  device
 * 
 * セッションの保存方法は3種類から選べる
 * (1)メモリ(memcached)に保存する → sessionHandlerMemを使用すること
 * (2)データベースに保存する → sessionHandlerDbを使用すること
 * (3)ファイルを指定のディレクトリに保存する → ハンドラを使用しないこと
 * 
 * セッションの読み方
 * new Session;
 * $a = $_SESSION['example'];
 * 
 * セッションの書き方
 * $_SESSION['example'] = $a;//配列でも可
 * デバッグに書き込みしたことを表示させる場合は、コントローラで明示的に
 * session_write_close();
 * を実行させること
 * 
 * セッションの消し方
 * setcookie(PROJECT_PREFIX . 'login_sesid', '', time() - COOKIE_LIFETIME, '/'); // COOKIEを消す
 * session_destroy(); // DBまたはmemcacheのセッションレコードまたはセッションファイルを消す
 * 
 */

namespace Php\Framework\Device;

use SessionHandlerInterface;

class Session
{
    /**
     * コンストラクタ
     * @global bool $g_session_flag
     */
    public function __construct()
    {
        global $g_session_flag;
        $g_session_flag = true;
        $handler = new sessionHandlerMem();
        session_set_save_handler($handler, false);
        
        ini_set('session.gc_maxlifetime', COOKIE_LIFETIME);
        ini_set('session.cookie_lifetime', COOKIE_LIFETIME);
        ini_set('session.cookie_httponly', 1);
        session_save_path(SERVER_PATH . 'session');
        session_name(PROJECT_PREFIX . 'login_sesid');
        $secure = ENV >= ENV_STA ? true : false;
        session_set_cookie_params(COOKIE_LIFETIME, '/', '', $secure, true);
        session_start();
    }

    /**
     * セッションIDの値を変えてCOOKIEを更新
     * @return void
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
        create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        update_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (`session_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
 * 
 */
class sessionHandlerDb implements SessionHandlerInterface
{
    /**
     * 開く
     * @param string $path
     * @param string $name
     * @return bool
     */
    public function open(string $path, string $name): bool
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
     * @param string $id
     * @return string|false
     */
    public function read(string $id): string|false
    {
        $read = '';
        $params = [];
        $params['session_id'] = $id;
        $params['session_expires'] = time();
        S::$dbm->select(
            DB_NAME . '.t_session',
            'session_value',
            'WHERE session_id = :session_id AND '
            . 'session_expires > :session_expires',
            'session'
        );
        S::$dbm->bind($params, 'session');
        $res = S::$dbm->fetch('session');
        if ($res) {
            $read = $res['session_value'];
        }
        return $read;
    }

    /**
     * 書き込み
     * @param string $id
     * @param string $data
     * @return bool
     */
    public function write(string $id, string $data): bool
    {
        $params = [];
        $params['session_id'] = $id;
        $params['session_value'] = $data;
        $params['session_expires'] = time() + COOKIE_LIFETIME;
        S::$dbm->insert('t_session', $params, true, 'session');
        S::$dbm->bind($params, 'session');
        return true;
    }

    /**
     * 削除
     * @param string $id
     * @return bool
     */
    public function destroy(string $id): bool
    {
        $params = [];
        $params['session_id'] = $id;
        S::$dbm->delete(
            DB_NAME . '.t_session',
            'WHERE session_id = :session_id',
            'session'
        );
        S::$dbm->bind($params, 'session');
        return true;
    }

    /**
     * ガベージコレクション
     * @param int $max_lifetime
     * @return int|false
     */
    public function gc(int $max_lifetime): int|false
    {
        $params = [];
        $params['session_expires'] = time();
        S::$dbm->delete(
            DB_NAME . '.t_session',
            'WHERE session_expires < :session_expires',
            'session' . $max_lifetime
        );
        return S::$dbm->bind($params, 'session' . $max_lifetime);
    }
}

/**
 * セッションハンドラのカスタマイズ(memcachedに保存する場合)
 */
class sessionHandlerMem implements SessionHandlerInterface
{
    /**
     * 開く
     * @param string $path
     * @param string $name
     * @return bool
     */
    public function open(string $path, string $name): bool
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
     * @param string $id
     * @return string|flase
     */
    public function read(string $id): string|false
    {
        $res = S::$mem->get($id);
        return (string)$res;
    }

    /**
     * 書き込み
     * @param string $id
     * @param string $data
     * @return bool
     */
    public function write(string $id, string $data): bool
    {
        S::$mem->set($id, $data, time() + COOKIE_LIFETIME);
        return true;
    }

    /**
     * 削除
     * @param string $id
     * @return bool
     */
    public function destroy(string $id): bool
    {
        S::$mem->delete($id);
        return true;
    }

    /**
     * ガベージコレクション
     * @param int $max_lifetime
     * @return int|false
     */
    public function gc(int $max_lifetime): int|false
    {
        $param = ['expires' => TIMESTAMP];
        $where = 'WHERE temp_flag = 1 AND expires < :expires';
        S::$dbm->delete(DB_NAME . '.memcached', $where, 'gc' . $max_lifetime);
        return S::$dbm->bind($param, 'gc' . $max_lifetime);
    }
}
