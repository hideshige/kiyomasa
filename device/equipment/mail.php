<?php
/**
 * メール モジュール
 *
 * @author   Sawada Hideshige
 * @version  1.0.7.1
 * @package  device/equipment
 *
 */

namespace Php\Framework\Device\Equipment;

class Mail
{
    /**
     * メール送信
     * @param string $to 送信先メールアドレス
     * @param string $subject 件名
     * @param string $body 本文
     * @param string $cc CC
     * @return bool
     * @throws \Error
     */
    public static function sendMail(
        string $to,
        string $subject,
        string $body,
        string $cc = ''
    ): bool {
        $to2 = str_replace(array ("\n", "\r"), '', $to);
        mb_internal_encoding('ISO-2022-JP');
        $from_name = mb_encode_mimeheader(mb_convert_encoding(FROM_NAME,
            'ISO-2022-JP', 'utf8'), 'ISO-2022-JP', 'B');
        mb_internal_encoding('utf8');

        $subject2 = $subject;
        $subject3 = mb_convert_encoding($subject2, 'ISO-2022-JP', 'utf8');
        $subject4 = '=?iso-2022-jp?B?' . base64_encode($subject3) . '?=';
        $body2 = $body;
        $body3 = mb_convert_encoding($body2, 'ISO-2022-JP', 'utf8');
        $headers = "MIME-Version: 1.0 \n";
        $headers .= sprintf("From: %s<%s> \n", $from_name, FROM_EMAIL);
        $headers .= sprintf("Reply-To: %s<%s> \n", $from_name, FROM_EMAIL);
        if ($cc) {
            $headers .= sprintf("CC: %s \n",
                str_replace(array ("\n", "\r"), '', $cc));
        }
        $headers .= "Content-Type: text/plain;charset=ISO-2022-JP \n";
        $f = sprintf('-f%s', EMAIL_RETURN_PATH);

        $res = mail($to2, $subject4, $body3, $headers, $f);
        if ($res === false) {
            throw new \Error('send mail error');
        }
        return true;
    }
}
