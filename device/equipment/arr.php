<?php
/**
 * 配列関連 モジュール
 * 
 * @author   Sawada Hideshige
 * @version  1.0.0.0
 * @package  device/equipment
 */

namespace Php\Framework\Device\Equipment;

class Arr
{
    /**
     * 配列内に特定のキーが存在していれば取り除く
     * @param array $array 元の配列（参照渡し）
     * @param array $remove 取り除くキー群
     * @return void
     */
    public static function issetUnset(array &$array, array $remove): void
    {
        foreach ($remove as $v) {
            if (isset($array[$v])) {
                unset($array[$v]);
            }
        }
    }
    
    /**
     * 配列内に値がない場合取り除く
     * @param array $array
     * @return void
     */
    public static function zeroUnset(array &$array): void
    {
        foreach ($array as $k => $v) {
            if (empty($v)) {
                unset($array[$k]);
            }
        }
    }
}
