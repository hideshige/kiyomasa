<?php
/**
 * メール モジュール
 *
 * @author   Sawada Hideshige
 * @version  1.1.3.0
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
     * @param array $attachment_paths 添付ファイルパス
     * @return bool
     * @throws \Error
     */
    public static function sendMail(
        string $to,
        string $subject,
        string $body,
        string $cc = '',
        array $attachment_paths = []
    ): bool {
        // メール送信先
        $to2 = self::separate(str_replace(["\n", "\r"], '', $to));
        mb_internal_encoding('utf8');
        // メール送信元
        $from_name = mb_encode_mimeheader(self::$from_name, 'utf8', 'B');
        // メールの件名
        $subject2 = '=?utf8?B?' . base64_encode($subject) . '?=';
        $headers = "MIME-Version: 1.0 \n";
        $headers .= sprintf("From: %s<%s> \n", $from_name, self::$from_email);
        $headers .= sprintf("Reply-To: %s<%s> \n", $from_name, self::$from_email);
        if ($cc) {
            $headers .= 'CC: ' . self::separate($cc) . "\n";
        }

        if ($attachment_paths == []) {
            // テキストのみの場合
            $headers .= "Content-Type: text/plain;charset=utf8 \n";
            // メール本文
            $mail_body = $body;
        } else {
            // 添付ファイルありの場合
            // 境界文字列
            $boundary = md5(uniqid(time()));
            $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\n";
            // メール本文
            $mail_body = "--$boundary\n";
            $mail_body .= "Content-Type: text/plain; charset=utf8 \n";
            $mail_body .= "Content-Transfer-Encoding: 7bit \n\n";
            $mail_body .= $body . "\n";
            // 添付ファイル読み込み
            foreach ($attachment_paths as $path) {
                if (file_exists($path)) {
                    $attachment = chunk_split(
                        base64_encode(file_get_contents($path)));
                    
                    // 添付ファイル
                    $mail_body .= "--$boundary\n";
                    $mail_body .= "Content-Type: application/octet-stream; name=\"" . basename($path) . "\"\n";
                    $mail_body .= "Content-Transfer-Encoding: base64 \n";
                    $mail_body .= "Content-Disposition: attachment; filename=\"" . basename($path) . "\"\n\n";
                    $mail_body .= $attachment . "\n";
                } else {
                    die('ファイルが存在しません');
                }
            }
            $mail_body .= "--$boundary--";
        }
        $f = sprintf('-f%s', self::$return_email);

        $res = mail($to2, $subject2, $mail_body, $headers, $f);
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
