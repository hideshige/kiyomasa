<?php
/**
 * モデルの書き方　チュートリアル
 *
 * @author   Hideshige Sawada
 * @version  1.0.0.0
 * @package  model
 */

namespace Yourname\Yourproject\Model;

use Php\Framework\Kiyomasa\Device as D;

class HelloWorld // クラス名はファイル名に合わせる
{
    public $tpl = ['header', 'hello_world', 'footer']; // 使用するテンプレートのファイル名を指定する。.htmlは省略可

    public function logic()
    {
        try {
            D\S::$disp[0]['REPLACE']['title'] = 'ハローワールド';
            D\S::$disp[1]['MESSAGE_AREA'][0]['word'] = 'WORLD!';
            D\S::$disp[1]['MESSAGE_AREA'][1]['word'] = 'Japan.';
            D\S::$disp[1]['MESSAGE_AREA'][2]['word'] = 'Tokyo?';
            /*
            D\S::$disp[0] とは $this->tpl[0] のテンプレートを意味する
            D\S::$disp[0]['REPLACE']['title']とは、$this->tpl[0] のテンプレートの中の{title}を一括置換することを意味する
            D\S::$disp[1]['MESSAGE_AREA'][0]とは、$this->tpl[1] のテンプレート内の<!-- BEGIN MESSAGE_AREA -->～<!-- END MESSAGE_AREA -->を意味する
            D\S::$disp[1]['MESSAGE_AREA'][1]とは、$this->tpl[1] のテンプレート内で<!-- BEGIN MESSAGE_AREA -->～<!-- END MESSAGE_AREA -->を2回繰り返し、その2回目の方を意味する
            D\S::$disp[1]['MESSAGE_AREA'][0]['word']とは、$this->tpl[1] のテンプレート内で<!-- BEGIN MESSAGE_AREA -->～<!-- END MESSAGE_AREA -->の中の{word}を意味する
            */
        } catch (D\FwException $e) {
        } finally {
        }
    }
}

