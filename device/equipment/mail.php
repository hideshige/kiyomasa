<?php
/**
 * メール モジュール
 *
 * @author   Sawada Hideshige
 * @version  1.1.2.0
 * @package  device/equipment
 *
 */

namespace Php\Framework\Device\Equipment;

class Mail
{
    public static string $from_name = FROM_NAME;
    public static string $from_email = FROM_EMAIL;
    public static string $return_email = EMAIL_RETURN_PATH;
    
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
        $to2 = self::separate(str_replace(["\n", "\r"], '', $to));
        
        mb_internal_encoding('utf8');
        $from_name = mb_encode_mimeheader(self::$from_name, 'utf8', 'B');
        
        $subject2 = '=?utf8?B?' . base64_encode($subject) . '?=';
        $headers = "MIME-Version: 1.0 \n";
        $headers .= sprintf("From: %s<%s> \n", $from_name, self::$from_email);
        $headers .= sprintf("Reply-To: %s<%s> \n", $from_name, self::$from_email);
        if ($cc) {
            $headers .= 'CC: ' . self::separate($cc) . "\n";
        }
        $headers .= "Content-Type: text/plain;charset=utf8 \n";
        $f = sprintf('-f%s', self::$return_email);

        $res = mail($to2, $subject2, $body, $headers, $f);
        if ($res === false) {
            throw new \Error('send mail error');
        }
        return true;
    }
    
    /**
     * メールアドレスを分ける
     * @param string $meta
     * @return string
     */
    private static function separate(string $meta): string
    {
        $match = [];
        preg_match_all('/(.*?)<(.*?)>/',
            str_replace([';', ',', "\n", "\r"], '', $meta), $match);
        
        if (isset($match[2][0])) {
            $arr = [];
            foreach ($match[1] as $k => $v) {
                $arr[] = sprintf("%s<%s>",
                    mb_encode_mimeheader(trim($v), 'utf8', 'B'),
                    $match[2][$k]);
            }
            $str = implode(',', $arr);
        } else {
            $str = $meta;
        }
        return $str;
    }
}
