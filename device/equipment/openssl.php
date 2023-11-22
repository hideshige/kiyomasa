<?php
/**
 * 暗号化(OpenSSL) モジュール
 *
 * @author   Sawada Hideshige
 * @version  1.0.3.1
 * @package  device/equipment
 * 
 */

namespace Php\Framework\Device\Equipment;

class Openssl
{
    private static $private_key = null;
    private static $public_key = null;
    
    /**
     * 秘密鍵を使って暗号化
     * @param string $plain 暗号化したい文字
     * @return string 暗号化した文字
     */
    public static function encrypt(string $plain): string
    {
        $crypt = '';
        if ($plain) {
            if (!self::$private_key) {
                self::$private_key = openssl_get_privatekey(
                    OPEN_SSL_PRIVATE_KEY,
                    OPEN_SSL_PASSPHRASE
                );
            }
            $arr = str_split($plain, 117);
            foreach ($arr as $v) {
                $temp = '';
                openssl_private_encrypt($v, $temp, self::$private_key);
                $crypt .= base64_encode($temp);
            }
        }
        return $crypt;
    }

    /**
     * 公開鍵を使って復号
     * @param string $crypt 暗号化した文字
     * @return string 復号した文字
     */
    public static function decrypt(string $crypt): string
    {
        $plain = '';
        if ($crypt) {
            if (!self::$public_key) {
                self::$public_key = openssl_get_publickey(OPEN_SSL_PUBLIC_KEY);
            }
            $arr = str_split($crypt, 172);
            foreach ($arr as $v) {
                $temp = '';
                openssl_public_decrypt(
                    base64_decode($v), $temp, self::$public_key);
                $plain .= $temp;
            }
        }
        return $plain;
    }

    /**
     * 秘密鍵と公開鍵の生成
     * （まず最初にこれを実行しておく）
     * @return array
     */
    public static function makeKey(): array
    {
        if (!extension_loaded('openssl')) {
            throw new Error('openssl is not installed');
        }
        $res = openssl_pkey_new(['private_key_bits' => 1024,
            'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        $private_key = '';
        openssl_pkey_export($res, $private_key, OPEN_SSL_PASSPHRASE);
        $key = openssl_pkey_get_details($res);
        $public_key = $key['key'];
        $keys = [$private_key, $public_key];
        return $keys;
    }
}
