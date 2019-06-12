<?php
/**
 * cURL(カール)　モジュール
 *
 * @author   Sawada Hideshige
 * @version  1.0.5.3
 * @package  device/equipment
 */

namespace Php\Framework\Device\Equipment;

class Curl
{
    /**
     * データを送信し結果XMLオブジェクトを取得
     *
     * @param string $url アクセスするURL
     * @param int $type 結果形式
     * @param string $post_data POSTするデータがある場合記入する
     * @param array $headers 送信するヘッダ
     * @param bool $disp_headers ヘッダを取得するか否か
     * @return array 結果データ(情報と内容)
     */
    public static function getRes(
        string $url,
        int $type = CURL_TYPE_XML,
        string $post_data = '',
        array $headers = [],
        bool $disp_headers = false
    ): array {
        $ch = self::header($url, $post_data, $headers, $disp_headers);

        $data = [];
        $data['content'] = curl_exec($ch);
        $data['info'] = curl_getinfo($ch);
        curl_close($ch);

        if ($data['content']) {
            if ($type === CURL_TYPE_XML) {
                $data['content'] = simplexml_load_string($data['content']);
            } else if ($type === CURL_TYPE_JSON) {
                $data['content'] = json_decode($data['content']);
            }
        }
        return $data;
    }
    
    /**
     * cURLハンドルの作成とヘッダの設定
     * @param string $url
     * @param string $post_data
     * @param array $headers
     * @param bool $disp_headers
     * @return resource
     */
    private static function header(
        string $url,
        string $post_data,
        array $headers,
        bool $disp_headers
    ) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, $disp_headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        // cacert.pemを作って用意しておく
        curl_setopt($ch, CURLOPT_CAINFO,
            SERVER_PATH . 'device/equipment/cacert.pem');
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
        return $ch;
    }
}
