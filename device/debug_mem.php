<?php
/**
 * memcached モジュール（デバッグ用）
 *
 * @author   Sawada Hideshige
 * @version  1.0.2.0
 * @package  device
 * 
 */

namespace Php\Framework\Device;

class DebugMem extends Mem
{
    private string $disp_mem = '';//debug情報

    /**
     * 接続
     */
    public function __construct()
    {
        if (!extension_loaded('memcached')) {
            $this->disp_mem .= "Memcached is not installed. Execute it using DB.\n";
        } else if ($this->active === false) {
            $this->disp_mem .= "Memcached is down. Execute it using DB.\n";
        }
        parent::__construct();
    }

    /**
     * memcached に保存する
     * @param string $key キー
     * @param int|string|array $var 値
     * @param int $expire 有効期限
     * @return int|bool
     */
    public function set(string $key, $var, int $expire = 0)
    {
        if ($this->active) {
            $bt = debug_backtrace();
            $dump = sprintf("%s(%s)", $bt[0]['file'], $bt[0]['line']);
            $this->disp_mem .= sprintf("■SET %s\n[K]%s [V]%s\n",
                $dump, $key, print_r($var, true));
        }
        $res = parent::set($key, $var, $expire);
        return $res;
    }

    /**
     * memcach から値を取得する
     * @param string $key キー
     * @return array|bool
     */
    public function get(string $key)
    {
        $var = parent::get($key);
        if ($this->active and $var !== false) {
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
        $check = parent::delete($key);
        if ($check and $this->active) {
            $bt = debug_backtrace();
            $dump = sprintf("%s (%s)", $bt[0]['file'], $bt[0]['line']);
            $this->disp_mem .= sprintf("■DELETE %s\n[K]%s\n", $dump, $key);
        }
        return $check;
    }
    
    /**
     * memcach を一括削除する
     * @param bool $db_flag DBのデータも消す場合true
     * @return bool
     */
    public function flush(bool $db_flag = false)
    {
        $check = parent::flush($db_flag);
        if ($check and $this->active) {
            $bt = debug_backtrace();
            $dump = sprintf("%s (%s)", $bt[0]['file'], $bt[0]['line']);
            $this->disp_mem .= sprintf("■FLUSH %s\n", $dump);
        }
        return $check;
    }
    
    /**
     * デバッグ表示用文字列の取得
     * @return string
     */
    public function getDispMem(): string
    {
        return $this->disp_mem;
    }
}
