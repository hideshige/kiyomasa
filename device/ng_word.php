<?php
/**
 * 禁止ワードチェック モジュール
 *
 * @author   Sawada Hideshige
 * @version  1.0.3.1
 * @package  device
 *
 */

namespace Php\Framework\Device;

class NgWord
{
    /**
     * 禁止ワードが含まれているかどうか確認し、発見した禁止ワードを配列で返す
     * @param string $word 確認する文字列
     * @param bool $account_flag アカウント用文字列の場合TRUE、コメント用文字列の場合FALSEを指定
     * @param bool $mb4_flag 4バイト文字を有効にする場合TRUE
     * @return array 発見した禁止ワードを入れた配列
     */
    public static function check(
        string $word,
        bool $account_flag = false,
        bool $mb4_flag = false
    ): array {
        $box = [];

        // 4バイト文字を許可しない場合
        if (preg_match('/[\xF0-\xF4]/', $word) and !$mb4_flag) {
            $box[] = '(4バイト文字)';
        }

        // 文字コードをUTF-8にする
        $code = mb_detect_encoding($word);
        if ($code != 'UTF-8') {
            $word = mb_convert_encoding($word, 'UTF-8', $code);
        }

        // 半角カタカナとひらがなを全角カタカナに、英数字を半角に変換
        $word = mb_convert_kana($word, 'KVCa', 'UTF-8');

        // 濁音・半濁音・促音・拗音・長音の変換
//        $kana1 = ['ァ','ィ','ゥ','ェ','ォ','ッ','ャ','ュ','ョ','ヴ','ガ','ギ','グ','ゲ','ゴ','ザ','ジ','ズ','ゼ','ゾ','ダ','ヂ','ヅ','デ','ド','バ','ビ','ブ','ベ','ボ','パ','ピ','プ','ペ','ポ','ー'];
//        $kana2 = ['ア','イ','ウ','エ','オ','ツ','ヤ','ユ','ヨ','ウ','カ','キ','ク','ケ','コ','サ','シ','ス','セ','ソ','タ','チ','ツ','テ','ト','ハ','ヒ','フ','ヘ','ホ','ハ','ヒ','フ','ヘ','ホ',''];
//        $word = str_replace($kana1, $kana2, $word);

        // アクセント記号・ウムラウト記号・ラテン記号の統一
        $ab1 = ['ā','ē','ī','ō','ū','Ā','Ē','Ī','Ō','Ū','Â','Ê','Î','Ô','Û','Ŷ','â','ê','î','ô','û','ŷ','À','È','Ì','Ò','Ù','à','è','ì','ò','ù','Ã','Ĩ','Õ','Ũ','ã','ĩ','õ','ũ','ñ','Ä','Ë','Ï','Ö','Ü','Ÿ','ä','ë','ï','ö','ü','ÿ','ß'];
        $ab2 = ['a','e','i','o','u','a','e','i','o','u','a','e','i','o','u','y','a','e','i','o','u','y','a','e','i','o','u','a','e','i','o','u','a','i','o','u','a','i','o','u','n','a','e','i','o','u','y','a','e','i','o','u','y','ss'];
        $word = str_replace($ab1, $ab2, $word);

        // 記号と空白と改行を削除する
        $word = preg_replace('/[‐-℃|←-♯|〆-〕|！-＠|［-￥|!-\/|:-@|\[-`]|[\n×÷~{}、。・゛゜´¨ヽヾゝゞ〃仝々±°§Å〝〟　 ]/uis', '', $word);

        // 禁止ルールの読み込み
        if ($account_flag) {
            // アカウント名用禁止ルール（配列をJSONエンコードしてBASE64エンコードした文字列を指定する）
            $ng_code = '';
        } else {
            // コメント用禁止ルール（配列をJSONエンコードしてBASE64エンコードした文字列を指定する）
            $ng_code = '';
        }

        // 禁止ワードの全文検索
        $ng_code = json_decode(base64_decode($ng_code));
        foreach ($ng_code as $v) {
            // 禁止ワードも濁音・半濁音を変換しておく
            $check_word = $v;
//            $check_word = str_replace($kana1, $kana2, $check_word);
            $check_word = mb_convert_kana($check_word, 'KVCa', 'UTF-8');

            // 部分一致の確認
            $res = preg_match('/' . $check_word . '/uis', $word);
            if ($res) {
                // ワイルドカードをスペースに変換して配列に入れる
                $box[] = preg_replace('/\.\*/', ' ', $v);
            }
        }
        return $box;
    }
}
