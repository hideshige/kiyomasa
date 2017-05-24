<?php
/**
 * 暗号化(mcrypt) モジュール
 *
 * ※メモ
 * UTF-8で日本語の暗号化データをDBに保存する場合、
 * VARCHAR(255)に収めるためには暗号化する文字を62文字以内にする必要がある。
 *
 * @author   Sawada Hideshige
 * @version  1.0.2.2
 * @package  device
 */

namespace Php\Framework\Device;

use Php\Framework\Device\SystemError;

class Crypt
{
    /**
     * 暗号化・復合して結果データを取得
     *
     * @param array or string $data 暗号化・復合するデータ
     * @param string $encode_flag 暗号化・復合の選択指定 暗号化はTRUE 復号はFALSE
     * @return array or string or null 暗号化・復合された結果データ
     */
    private static function open($data, $encode_flag = true)
    {
        if (!extension_loaded('mcrypt')) {
            throw new Error('mcrypt is not installed');
        }
        
        $res = null;
        if ($data) {
            // 暗号化モジュール開始
            $td = mcrypt_module_open(MCRYPT_BLOWFISH, '', MCRYPT_MODE_CBC, '');
            $res = mcrypt_generic_init(
                $td,
                substr(CRYPT_KEY, 0, mcrypt_enc_get_key_size($td)),
                substr(CRYPT_IV, 0, mcrypt_enc_get_iv_size($td))
            );
            if ($res < 0) {
                mcrypt_module_close($td);
                throw new Error('暗号化エラー');
            }
            // dataを暗号化または復号
            if (!$encode_flag) {
                $data = base64_decode($data);
            }
            $data = $encode_flag
                ? mcrypt_generic($td, $data) : mdecrypt_generic($td, $data);
            mcrypt_generic_deinit($td);
            mcrypt_module_close($td);
            if ($encode_flag) {
                $data = base64_encode($data);
            } else {
                $data = trim($data);
            }
        }
        return $data;
    }

    public static function encrypt($data)
    {
        return self::open($data, true);
    }
    
    public static function decrypt($data)
    {
        return self::open($data, false);
    }
}
