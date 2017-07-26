<?php
/**
 * memcached モジュール
 *
 * @author   Sawada Hideshige
 * @version  1.0.8.1
 * @package  device
 * 
 * DBで無期限データ用バックアップテーブルを準備しておく
 * なお、MemcachedがインストールされていないサーバでもDBで代用可能
CREATE TABLE memcached (
    memcached_key VARCHAR(255) NOT NULL,
    memcached_value TEXT,
    temp_flag TINYINT NOT NULL DEFAULT 0,
    expire DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT NULL,
    updated_at DATETIME DEFAULT NULL,
    PRIMARY KEY (memcached_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
 *
 */

namespace Php\Framework\Device;

class Mem
{
    private $memcached_1;//memcachedオブジェクト
    private $active;//memcachedが起動しているかどうかのフラグ
    public $disp_mem = '';//debug情報
    public $debug;//debugかどうかのフラグ

    /**
     * 接続
     */
    public function __construct()
    {
        if (!extension_loaded('memcached')) {
            // Memcachedがインストールされていない
            $this->active = false;
            $this->disp_mem .= "Memcached is not installed. Execute it using DB.\n";
        } else {
            $this->memcached_1 = new \Memcached();

            // 主がNGの場合は副を使用
            $this->active = $this->memcached_1->addServer(MEMCACHED_SERVER, 11211);
            if ($this->active === false) {
                // Memcachedがダウンしている
                Log::error('Memcached down');
                $this->disp_mem .= "Memcached is down. Execute it using DB.\n";
            }
        }
    }

    /**
     * memcached に保存する
     * @param string $key キー
     * @param string|array $var 値
     * @param int $expire 有効期限
     * @return int|bool
     */
    public function set(string $key, string $var, int $expire = 0)
    {
        if ($this->debug and $this->active) {
            $bt = debug_backtrace();
            $dump = sprintf("%s(%s)", $bt[0]['file'], $bt[0]['line']);
            $this->disp_mem .= sprintf("■SET %s\n[K]%s [V]%s\n",
                $dump, $key, print_r($var, true));
        }
        if ($this->active) {
            $res = $this->memcached_1->set($key, $var, $expire);
        }

        // memcachedが有効でない場合か有効期限の指定がない場合DBに値を保存
        if ($this->active === false or $expire === 0) {
            $res = $this->dbSet($key, $var, $expire);
        }
        return $res;
    }

    /**
     * memcach から値を取得する
     * @param string $key キー
     * @return array|bool
     */
    public function get(string $key)
    {
        if ($this->active) {
            $var = $this->memcached_1->get($key);
        } else {
            $var = false;
        }
        if ($var === false) {
            $var = $this->dbSelect($key);
        }
        if ($this->debug and $this->active and $var !== false) {
            $bt = debug_backtrace();
            $dump = sprintf("%s (%s)", $bt[0]['file'], $bt[0]['line']);
            $this->disp_mem .= sprintf("■GET %s\n[K]%s [V]%s\n",
                $dump, $key, print_r($var, true));
        }
        return $var;
    }
    
    /**
     * memcach から値を削除する
     * @param string $key キー
     * @return bool
     */
    public function delete(string $key)
    {
        $check = true;
        if ($this->active) {
            $check = $this->memcached_1->delete($key);
        }
        if ($check) {
            $this->dbDelete($key);
        }

        if ($check and $this->debug and $this->active) {
            $bt = debug_backtrace();
            $dump = sprintf("%s (%s)", $bt[0]['file'], $bt[0]['line']);
            $this->disp_mem .= sprintf("■DELETE %s\n[K]%s\n", $dump, $key);
        }
        return $check;
    }
    
    /**
     * データベースから抽出
     * @param string $key
     * @return mixed
     */
    private function dbSelect(string $key)
    {
        $var = false;
        $param = ['memcached_key' => $key];
        $where = 'WHERE memcached_key = :memcached_key';
        S::$dbs->select('memcached', '*', $where, 'memcached');
        S::$dbs->bind($param, 'memcached');
        $res = S::$dbs->fetch('\stdClass', 'memcached');
        if (($res === false or ($res->temp_flag and 
            strtotime($res->expire) < time())) === false) {
            $var = unserialize($res->memcached_value);
            $expire = $res->temp_flag ? time() + COOKIE_LIFETIME : 0;
            if ($this->active) {
                //データベースの値をmemcachedに保存
                $this->memcached_1->set($key, $var, false, $expire);
            }
        }
        return $var;
    }

    /**
     * データベースに保存する
     * @param string $key キー
     * @param mixed $var 値
     * @param int $expire 有効期限
     * @return int|bool
     */
    private function dbSet(string $key, $var, int $expire)
    {
        $temp_flag = $expire ? 1 : 0;
        $params = [];
        $params['memcached_key'] = $key;
        $params['memcached_value'] = serialize($var);
        $params['temp_flag'] = $temp_flag;
        $params['expire'] = $expire
            ? date('Y-m-d H:i:s', $expire) : TIMESTAMP;
        $params['created_at'] = TIMESTAMP;
        S::$dbm->insert('memcached', $params, true, 'memcached');
        $res = S::$dbm->bind($params, 'memcached');
        return $res;
    }
    
    /**
     * データベースから削除
     * @param string $key
     * @return void
     */
    private function dbDelete(string $key): void
    {
        $param = [$key];
        $where = 'WHERE memcached_key = ?';
        S::$dbm->delete('memcached', $where, 'memcached');
        S::$dbm->bind($param, 'memcached');
    }
}
