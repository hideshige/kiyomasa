<?php
/**
 * memcached モジュール
 *
 * @author   Sawada Hideshige
 * @version  1.0.7.1
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

use Memcached;

class Mem
{
    private $memcached1;//memcachedオブジェクト
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
            $this->memcached1 = new Memcached();

            // 主がNGの場合は副を使用
            $this->active = $this->memcached1->addServer(MEMCACHED_SERVER, 11211);
            if (!$this->active) {
                // Memcachedがダウンしている
                Log::error('Memcached down');
                $this->disp_mem .= "Memcached is down. Execute it using DB.\n";
            }
        }
    }

    /**
     * memcached に保存する
     * @param string $key キー
     * @param string or array $var　値
     * @param datetime $expire 有効期限
     */
    public function set($key, $var, $expire = 0)
    {
        if ($this->debug and $this->active) {
            $bt = debug_backtrace();
            $dump = sprintf("%s(%s)", $bt[0]['file'], $bt[0]['line']);
            $this->disp_mem .= sprintf(
                "■SET %s\n[K]%s [V]%s\n",
                $dump,
                $key,
                print_r($var, true)
            );
        }
        if ($this->active) {
            $res = $this->memcached1->set($key, $var, $expire);
        }

        // memcachedが有効でない場合か有効期限の指定がない場合DBに値を保存
        if (!$this->active or !$expire) {
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
        }
        return $res;
    }

    /**
     * memcach から値を取得する
     * @param string $key キー
     */
    public function get($key)
    {
        if ($this->active) {
            $var = $this->memcached1->get($key);
        } else {
            $var = false;
        }
        if ($var === false) {
            //データベースから値を取得
            $param = ['memcached_key' => $key];
            $where = 'WHERE memcached_key = :memcached_key';
            S::$dbs->select('memcached', '*', $where, 'memcached');
            $res = S::$dbs->bindSelect($param, 'memcached');
            if (!(!$res or ($res[0]['temp_flag'] and 
                strtotime($res[0]['expire']) < time()))) {
                $var = unserialize($res[0]['memcached_value']);
                $expire = $res[0]['temp_flag'] ? time() + COOKIE_LIFETIME : 0;
                if ($this->active) {
                    //データベースの値をmemcachedに保存
                    $this->memcached1->set($key, $var, false, $expire);
                }
            }
        }
        if ($this->debug and $this->active and $var !== false) {
            $bt = debug_backtrace();
            $dump = sprintf("%s (%s)", $bt[0]['file'], $bt[0]['line']);
            $this->disp_mem .= sprintf(
                "■GET %s\n[K]%s [V]%s\n",
                $dump,
                $key,
                print_r($var, true)
            );
        }
        return $var;
    }

    /**
     * memcach から値を削除する
     * @param string $key キー
     */
    public function delete($key)
    {
        $check = true;
        if ($this->active) {
            $check = $this->memcached1->delete($key);
        }
        if ($check) {
            $param = array ($key);
            $where = 'WHERE memcached_key = ?';
            S::$dbm->delete('memcached', $where, 'memcached');
            S::$dbm->bind($param, 'memcached');
        }

        if ($check and $this->debug and $this->active) {
            $bt = debug_backtrace();
            $dump = sprintf("%s (%s)", $bt[0]['file'], $bt[0]['line']);
            $this->disp_mem .= sprintf("■DELETE %s\n[K]%s\n", $dump, $key);
        }
        return $check;
    }
}
