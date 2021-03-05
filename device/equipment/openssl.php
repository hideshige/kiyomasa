<?php
/**
 * 暗号化(OpenSSL) モジュール
 *
 * @author   Sawada Hideshige
 * @version  1.0.2.0
 * @package  device/equipment
 * 
 * 
 */

namespace Php\Framework\Device\Equipment;

class Openssl
{
    
    /**
     * 秘密鍵を使って暗号化
     * @param string $plain 暗号化したい文字
     * @return string 暗号化した文字
     */
    public static function encrypt(string $plain): string
    {
        $crypt = '';
        if ($plain) {
            $res = openssl_get_privatekey(
                OPEN_SSL_PRIVATE_KEY,
                OPEN_SSL_PASSPHRASE
            );
            openssl_private_encrypt($plain, $crypt, $res);
            $crypt = base64_encode($crypt);
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
            $crypt = base64_decode($crypt);
            $res = openssl_get_publickey(OPEN_SSL_PUBLIC_KEY);
            openssl_public_decrypt($crypt, $plain, $res);
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
