<?php
/**
 * クライアントURLモジュール
 *
 * @author   Hideshige Sawada
 * @version  1.0.5.1
 * @package  device
 */

namespace Bunroku\Kiyomasa\Device;

class Curl
{
    /**
     * データを送信し結果XMLオブジェクトを取得
     *
     * @param string $url アクセスするURL
     * @param integer $type 1:XML 2:JSON 3:TEXT
     * @param string $post_data POSTするデータがある場合記入する
     * @param array $headers 送信するヘッダ
     * @param boolean $disp_headers ヘッダを取得するか否か
     * @return array or false 結果データ(情報と内容)
     */
    public static function getRes(
        $url,
        $type = 1,
        $post_data = '',
        $headers = [],
        $disp_headers = false
    ) {
        //クライアントURLの実行
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, $disp_headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        // cacert.pemを用意しておく
        curl_setopt($ch, CURLOPT_CAINFO, SERVER_PATH . 'device/cacert.pem');
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        if ($headers) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        if ($post_data) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);

        $data = [];
        $data['content'] = curl_exec($ch);
        $data['info'] = curl_getinfo($ch);
        curl_close($ch);

        if ($data['content']) {
            if ($type == 1) {
                $data['content'] = simplexml_load_string($data['content']);
            } else if ($type == 2) {
                $data['content'] = json_decode($data['content']);
            }
        }
        return $data;
    }
}
