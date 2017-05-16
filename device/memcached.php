<?php
/**
 * memcached モジュール
 *
 * @author   Hideshige Sawada
 * @version  1.0.5.4
 * @package  device
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

namespace bunroku\kiyomasa\device;

use Memcached;

class MemcachedModule
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
        $this->memcached1 = new Memcached();

        //主がNGの場合は副を使用
        $this->active = $this->memcached1->addServer(MEMCACHED_SERVER, 11211);
        if (!$this->active) {
            Log::error('memcached down');
        }
    }

    /**
     * memcached に保存する
     * @param string $key キー
     * @param string or array $var　値
     * @param boolean $comp 圧縮の有無
     * @param datetime $expire 有効期限
     */
    public function set($key, $var, $comp = false, $expire = 0)
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
            S::$dbm->insert('memcached', $params, true);
            $res = S::$dbm->bind($params);
        }
        return $res;
    }

    /**
     * memcach から値を取得する
     * @param string $key キー
     */
    public function get($key)
    {
        $var = @$this->memcached1->get($key);
        if ($var === false) {
            //データベースから値を取得
            $param = array ($key);
            $where = 'WHERE memcached_key = ?';
            S::$dbs->select('memcached', '*', $where);
            $res = S::$dbs->bindSelect($param);
            if (!$res or ($res[0]['temp_flag'] and strtotime($res[0]['expire']) < time())) {
                return false;
            }

            $var = unserialize($res[0]['memcached_value']);
            $expire = $res[0]['temp_flag'] ? time() + COOKIE_LIFETIME : 0;

            if ($this->active) {
                //データベースの値をmemcachedに保存
                @$this->memcached1->set($key, $var, false, $expire);
            }
        }
        if ($this->debug and $this->active) {
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
    public function delete($key)
    {
        $res = $this->memcached1->delete($key);

        $param = array ($key);
        $where = 'WHERE memcached_key = ?';
        S::$dbm->delete('memcached', $where);
        S::$dbm->bind($param);

        if ($this->debug and $this->active) {
            $bt = debug_backtrace();
            $dump = sprintf("%s (%s)", $bt[0]['file'], $bt[0]['line']);
            $this->disp_mem .= sprintf("■DELETE %s\n[K]%s\n", $dump, $key);
        }
        return $res;
    }
}

