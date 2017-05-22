<?php
/**
 * 暗号化(OpenSSL) モジュール
 *
 * @author   Sawada Hideshige
 * @version  1.0.1.0
 * @package  device
 * 
 * 
 */

namespace Php\Framework\Device;

class Openssl {

    /**
     * 秘密鍵を使って暗号化
     * @param string $plain 暗号化したい文字
     * @return string 暗号化した文字
     */
    public static function encrypt($plain)
    {
        $crypt = null;
        if ($plain) {
            $res = openssl_get_privatekey(
                OPEN_SSL_PRIVATE_KEY,
                OPEN_SSL_PASSPHRASE
            );
            openssl_private_encrypt($plain, $crypt, $res);
            openssl_free_key($res);
            $crypt = base64_encode($crypt);
        }
        return $crypt;
    }

    /**
     * 公開鍵を使って復号
     * @param string $crypt 暗号化した文字
     * @return string 復号した文字
     */
    public static function decrypt($crypt) {
        $plain = null;
        if ($crypt) {
            $crypt = base64_decode($crypt);
            $res = openssl_get_publickey(OPEN_SSL_PUBLIC_KEY);
            openssl_public_decrypt($crypt, $plain, $res);
            openssl_free_key($res);
        }
        return $plain;
    }

    /**
     * 秘密鍵と公開鍵の生成
     * @return array
     */
    public static function makeKey()
    {
        $res = openssl_pkey_new(['private_key_bits' => 1024]);
        $private_key = '';
        openssl_pkey_export($res, $private_key, OPEN_SSL_PASSPHRASE);
        $key = openssl_pkey_get_details($res);
        $public_key = $key['key'];
        $keys = [$private_key, $public_key];
        return $keys;
    }
}

const OPEN_SSL_PASSPHRASE = 'ssl_keyword1'; // 任意の暗号
// 上記のmakeKeyにより以下を生成して追記しておく
const OPEN_SSL_PRIVATE_KEY = <<<EOT
-----BEGIN ENCRYPTED PRIVATE KEY-----
MIICxjBABgkqhkiG9w0BBQ0wMzAbBgkqhkiG9w0BBQwwDgQIMT7at7VImBsCAggA
MBQGCCqGSIb3DQMHBAgCmTxPmufFvQSCAoBqvkZBQ5rjubF5TbzFVfqAwKx3d7Sz
h52nDlY3B36HL4u0MhtWxGFWptHT8DlyDBiUKnLYGzt2bB2Ymb7FDwlrYtZbgHMa
4mhCo0FuvM1MLpzOLM/lzSdJy2fjANhn6+7UiEErR8osnbBmIKTzNQLn6ZUHJI32
lrPkiojZ9pboX7ptQ8afRg/kVV6xljaEX6rZJFXd0yYr5bZhrSPonG6spva2kXuj
LUIda7gTJz46/Oa2s79DQ7gQdll8lu6xrN3TzHbcIOt3rp6zNwjbdSwyaCqjXk1+
QC3gWY9gG2z3m/UsckMJ8WjaBuA7v6UEAgHmSGT9AnqOQQxIx3f+GqSRr6L2AZh0
zoWZuUztJwCUmIAtRwyET9eCNTx5rn0pBjBTAtawTtDsWJeZ/C+53KLp/ll+xiP1
gBYN5T/ROplD/E+DMSk/odD9nOHbSOWPkqXsULeeIWOqYwxOAlxQsO9SLx/e3qcY
W7LrpFFy1YsyOAFE+tv4TeeQsT4RrT80iyO+DPF6lTVGJ0/bkHU+j5PiHGz12BXg
XKr7ITRsTlES11LmpapkCfsEGf/HeLIEamfmlM7wTrPX8adcY7Uoi854ocvdXYR/
bIkSVNSk8+Y1NlSoKp8tJG0rXUNKaAg1b3tAoekLN41y6GVA3QQlN2y70GuiTI6F
qR8/rhsz0Un0B+S/t8KM7f8J4VkqtODeOvkcfsauq4jgLTnLK5siJmAbY1ULUwqF
PoMmrtcsSs2HH1u8/3MLyEVLW0JL3bbnC2mIl+0eFxvuU/JFXZSC5sT0tguvegbN
zWA2i3Wir+dxoH/ElUMRJF36t1yaYRpkfENiKTxwqp6s7Uu6sT8QdH1K
-----END ENCRYPTED PRIVATE KEY-----
EOT;
const OPEN_SSL_PUBLIC_KEY = <<<EOT
-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQC2HkZ9wInlTovcq07NSa5UOSHI
3rjRTKb5YJaZRzx1UsA/eC8cDwP24CvU68FBqFfkYM3lFWdQKKMBHva6VXfBU1XN
/7ii51meeFd2/tBTL0QOcm7utJsMO7pdeUpm69g6V5edpzoWeN0KT0/pEzEAd9DG
o/80+6TR6vFdGlDoxwIDAQAB
-----END PUBLIC KEY-----
EOT;
