<?php
/**
 * 使い方　チュートリアル
 *
 * @author   Sawada Hideshige
 * @version  1.0.0.0
 * @package  gate/content
 */

namespace Yourname\Yourproject\Gate\Content;

use Php\Framework\Device as D;
use Yourname\Yourproject\Base as B;

class HelloWorld extends B\BaseModel // クラス名はファイル名に合わせる
{
    public $tpl = ['part/header', 'content/hello_world', 'part/footer']; // 使用するテンプレートのファイル名を指定する。.htmlは省略可

    /**
     * 実行
     * @return void
     */
    protected function execute(): void
    {
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
        
        // throwの使い道は事前に二通り用意している。両方の動作を確認してみよう。
        //throw new D\UserEx('TEST111');
        //throw new \Error('TEST222');
    }
    
    /**
     * 例外処理
     * @param string $mes
     * @return bool
     */
    protected function throwCatch(string $mes): bool
    {
        unset(D\S::$disp[1]['MESSAGE_AREA']);
        D\S::$disp[1]['ERROR_AREA'][0]['word'] = $mes;
        return true;
    }
}
