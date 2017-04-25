<?php
/**
 * モデルの書き方　チュートリアル
 *
 * @author   Hideshige Sawada
 * @version  1.0.0.0
 */

class hello_world {//クラス名はモデル名と必ず同一にする
  public $tpl = array( 'header', 'hello_world', 'footer' );//使用するテンプレートのファイル名を指定する。.htmlは省略可
  public $equipment = array();//equipmentディレクトリから必要な機能を指定する
  public $common = array();//commonディレクトリから必要な機能を指定する

  public function logic() {
    S::$disp[0]['REPLACE']['title'] = 'ハローワールド';
    S::$disp[1]['MESSAGE_AREA'][0]['word'] = 'WORLD!';
    S::$disp[1]['MESSAGE_AREA'][1]['word'] = 'Japan.';
    S::$disp[1]['MESSAGE_AREA'][2]['word'] = 'Tokyo?';
    /*
    S::$disp[0] とは $this->tpl[0] のテンプレートを意味する
    S::$disp[0]['REPLACE']['title']とは、$this->tpl[0] のテンプレートの中の{title}を一括置換することを意味する
    S::$disp[1]['MESSAGE_AREA'][0]とは、$this->tpl[1] のテンプレート内の<!-- BEGIN MESSAGE_AREA -->～<!-- END MESSAGE_AREA -->を意味する
    S::$disp[1]['MESSAGE_AREA'][1]とは、$this->tpl[1] のテンプレート内で<!-- BEGIN MESSAGE_AREA -->～<!-- END MESSAGE_AREA -->を2回繰り返し、その2回目の方を意味する
    S::$disp[1]['MESSAGE_AREA'][0]['word']とは、$this->tpl[1] のテンプレート内で<!-- BEGIN MESSAGE_AREA -->～<!-- END MESSAGE_AREA -->の中の{word}を意味する
    */
  }
}

