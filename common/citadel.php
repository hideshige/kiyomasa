<?php
/**
 * シタデル　共通モデル
 * 
 * @author   Hideshige Sawada
 * @version  1.0.0.0
 * @package  common
 * 
 */

class citadel {
  private static $_db;//MASTER,SLAVE可変オブジェクト

  /**
   * デフォルト値のセット
   * @param string $title
   */
  public static function set( $title = '' ) {
    S::$disp[0]['REPLACE']['title'] = $title;
  }
  
  /**
   * MASTER,SLAVE接続先可変DBの選択
   */
  private static function _db_select() {
    //トランザクションを実行中には参照の場合でもMASTERに接続する
    if ( S::$dbm->transaction_flag ) {
      self::$_db = &S::$dbm;
    } else {
      self::$_db = &S::$dbs;
    }
  }
  
  /**
   * サニタイズした文字を戻す
   * @param string $word 戻したい文字
   * @return string 戻した文字
   */
  public static function h_decode($word) {
    $res = htmlspecialchars_decode($word, ENT_QUOTES);
    global $g_change_chara;
    if (!$g_change_chara) { return $res; } 
    foreach ($g_change_chara as $ck => $cv) {
      $res2 = str_replace($ck, $cv, $res);
    }
    return $res2;
  }
}

