<?php
/**
 * 文字列関連 モジュール
 * 
 * @author   Hideshige Sawada
 * @version  1.0.1.1
 * @package  equipment
 */

namespace Bunroku\Kiyomasa\Equipment;

class Chara
{
    /**
     * カナをローマ字に
     * @param string $kana カナ文字
     * @return string ローマ字
     */
    public static function kana2Latin($kana)
    {
        // 小文字を大文字にし、半角カナを全角カナにし、全角英数字を半角英数字にする
        $kana = strtoupper(mb_convert_kana($kana, 'CKVa', DEFAULT_CHARSET));
        // 変換は拗音を優先させる
        $kana_array = array (
            'ア' => 'A', 'イ' => 'I', 'ウァ' => 'WUA', 'ウィ' => 'WI', 'ウェ' => 'WE', 'ウォ' => 'WO', 'ウ' => 'U', 'エ' => 'E',
            'オ' => 'O', 'カ' => 'KA', 'キャ' => 'KYA', 'キュ' => 'KYU', 'キョ' => 'KYO', 'キ' => 'KI', 'クァ' => 'QUA',
            'クィ' => 'QUI', 'クェ' => 'QUE', 'クォ' => 'QUO', 'ク' => 'KU', 'ケ' => 'KE', 'コ' => 'KO', 'サ' => 'SA', 'シャ' => 'SHA',
            'シュ' => 'SHU', 'ショ' => 'SHO', 'シェ' => 'SHE', 'シ' => 'SHI', 'スィ' => 'SI', 'ス' => 'SU', 'セ' => 'SE', 'ソ' => 'SO', 'タ' => 'TA', 
            'チャ' => 'CHA', 'チュ' => 'CHU', 'チョ' => 'CHO', 'チェ' => 'CHE', 'チ' => 'CHI', 'ツァ' => 'TSA', 'ツィ' => 'TSI', 'ツェ' => 'TSE',
            'ツォ' => 'TSO', 'ツ' => 'TSU', 'ティ' => 'TI', 'テュ' => 'TWU', 'テ' => 'TE', 'トゥ' => 'TU', 'ト' => 'TO', 'ナ' => 'NA',
            'ニャ' => 'NYA', 'ニュ' => 'NYU', 'ニョ' => 'NYO', 'ニ' => 'NI', 'ヌ' => 'NU', 'ネ' => 'NE', 'ノ' => 'NO',
            'ハ' => 'HA', 'ヒャ' => 'HYA', 'ヒュ' => 'HYU', 'ヒョ' => 'HYO', 'ヒ' => 'HI', 'ファ' => 'FA', 'フィ' => 'FI',
            'フェ' => 'FE', 'フォ' => 'FO', 'フュ' => 'FU', 'フ' => 'FU', 'ヘ' => 'HE', 'ホ' => 'HO', 'マ' => 'MA', 'ミャ' => 'MYA',
            'ミュ' => 'MYU', 'ミョ' => 'MYO', 'ミ' => 'MI', 'ム' => 'MU', 'メ' => 'ME', 'モ' => 'MO', 'ヤ' => 'YA', 'ユ' => 'YU',
            'ヨ' => 'YO', 'ラ' => 'RA', 'リャ' => 'RYA', 'リュ' => 'RYU', 'リョ' => 'RYO', 'リ' => 'RI', 'ル' => 'RU', 'レ' => 'RE',
            'ロ' => 'RO', 'ワ' => 'WA', 'ヲ' => 'WO', 'ガ' => 'GA', 'ギャ' => 'GYA', 'ギュ' => 'GYU', 'ギョ' => 'GYO', 'ギ' => 'GI',
            'グァ' => 'GUA', 'グィ' => 'GUI', 'グェ' => 'GUE', 'グォ' => 'GUO', 'グ' => 'GU', 'ゲ' => 'GE', 'ゴ' => 'GO', 'ザ' => 'ZA',
            'ジャ' => 'JA', 'ジュ' => 'JU', 'ジョ' => 'JO', 'ジェ' => 'JE', 'ジ' => 'JI', 'ズィ' => 'ZI', 'ズ' => 'ZU', 'ゼ' => 'ZE', 'ゾ' => 'ZO',
            'ダ' => 'DA', 'ヂャ' => 'JA', 'ヂュ' => 'JU', 'ヂョ' => 'JO', 'ヂェ' => 'JE', 'ヂ' => 'JI', 'ヅァ' => 'ZUA', 'ヅィ' => 'ZUI', 'ヅェ' => 'ZUE',
            'ヅォ' => 'ZUO', 'ヅ' => 'ZU', 'ディ' => 'DI', 'デュ' => 'DYU', 'デ' => 'DE', 'ドゥ' => 'DU', 'ド' => 'DO', 'バ' => 'BA',
            'ビャ' => 'BYA', 'ビュ' => 'BYU', 'ビョ' => 'BYO', 'ビ' => 'BI', 'ブァ' => 'BUA', 'ブィ' => 'BUI', 'ブェ' => 'BUE',
            'ブォ' => 'BUO','ブュ' => 'BYU',  'ブ' => 'BU', 'ベ' => 'BE', 'ボ' => 'BO', 'パ' => 'PA', 'ピャ' => 'PYA', 'ピュ' => 'PYU',
            'ピョ' => 'PYO', 'ピ' => 'PI',  'プァ' => 'PUA', 'プィ' => 'PUI', 'プェ' => 'PUE', 'プォ' => 'PUO', 'プュ' => 'PYU',
            'プ' => 'PU', 'ペ' => 'PE', 'ポ' => 'PO', 'クヮ' => 'KWA', 'グヮ' => 'GWA', 'ヴァ' => 'VA', 'ヴィ' => 'VI',
            'ヴェ' => 'VE', 'ヴォ' => 'VO', 'ヴ' => 'VU', 'ヴュ' => 'VU', 'ヰ' => 'YI', 'ヱ' => 'YE'
        );
        $key = array_keys($kana_array);
        $latin = str_replace($key, $kana_array, $kana);
        // 促音はCHの前のみT、それ以外は後に続く文字を連ねて、長音は前の文字を連ねて書き換える
        $latin = preg_replace('/ッCH/', 'TCH', $latin);
        $latin = preg_replace('/ッ(.)|(.)ー/', '$1$1$2$2', $latin);
        // OOは次にO以外の母音が来ない場合OHにする,OUは次に母音以外の場合Oにする
        $latin = preg_replace('/OO(?![AIUE])/', 'OH$1', $latin);
        // $latin = preg_replace('/OU(?![AIUEO])/', 'O$1', $latin);
        // ンは母音の前はでN- 唇を閉じる音の前のみM、それ以外はNとする
        $latin = preg_replace('/ン([BMP])/', 'M$1', $latin);
        $latin = preg_replace('/ン([AIUEO])/', "N-$1", $latin);
        $latin = preg_replace('/ン/', 'N', $latin);
        return $latin;
    }
}
