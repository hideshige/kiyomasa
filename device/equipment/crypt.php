<?php
/**
 * 暗号化(mcrypt) モジュール（PHP7.2以上は使用できない）
 *
 * ※メモ
 * UTF-8で日本語の暗号化データをDBに保存する場合、
 * VARCHAR(255)に収めるためには暗号化する文字を62文字以内にする必要がある。
 *
 * @author   Sawada Hideshige
 * @version  1.0.3.0
 * @package  device/equipment
 */

namespace Php\Framework\Device\Equipment;

class Crypt
{
    /**
     * 暗号化・復合して結果データを取得
     * @param mixed $data 暗号化・復合するデータ(array|string)
     * @param bool $encode_flag 暗号化・復合の選択指定 暗号化はTRUE 復号はFALSE
     * @return mixed 暗号化・復合された結果データ(array|string|null)
     * @throws \Error
     */
    private static function open($data, bool $encode_flag = true)
    {
        if (!extension_loaded('mcrypt')) {
            throw new \Error('mcrypt is not installed');
        }
        
        if ($data) {
            $data = self::exec($data, $encode_flag);
        }
        return $data;
    }
    
    /**
     * 暗号化モジュール開始
     * @param mixed $data 暗号化・復合するデータ(array|string)
     * @param bool $encode_flag 暗号化・復合の選択指定 暗号化はTRUE 復号はFALSE
     * @return mixed 暗号化・復合された結果データ(array|string|null)
     * @throws \Error
     */
    private static function exec($data, bool $encode_flag)
    {
        $td = mcrypt_module_open(MCRYPT_BLOWFISH, '', MCRYPT_MODE_CBC, '');
        $res = mcrypt_generic_init($td,
            substr(CRYPT_KEY, 0, mcrypt_enc_get_key_size($td)),
            substr(CRYPT_IV, 0, mcrypt_enc_get_iv_size($td)));
        if ($res < 0) {
            mcrypt_module_close($td);
            throw new \Error('暗号化エラー');
        }
        // dataを暗号化または復号
        if ($encode_flag === false) {
            $data = unserialize(mdecrypt_generic($td, base64_decode($data)));
        } else {
            $data = base64_encode(mcrypt_generic($td, serialize($data)));
        }
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        return $data;
    }
    

    /**
     * 暗号化
     * @param mixed $data (array|string)
     * @return mixed (array|string|null)
     */
    public static function encrypt($data)
    {
        return self::open($data, true);
    }
    
    /**
     * 復号
     * @param mixed $data (array|string)
     * @return mixed (array|string|null)
     */
    public static function decrypt($data)
    {
        return self::open($data, false);
    }
}
