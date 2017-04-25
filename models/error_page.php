<?php
/**
 * error モデル
 *
 * @author   Hideshige Sawada
 * @version  1.0.0.0
 */

class error_page {
  public $tpl = array('header', 'error', 'footer');

  public function logic() {
    $title = 'エラー';
    citadel::set($title);
    $message = 'ページが見つかりません。';
    if (isset($_SESSION['error_message'])) {
      $message = $_SESSION['error_message'];
      unset($_SESSION['error_message']);
    }
    S::$disp[1]['MESSAGE'][0]['message'] = $message;
  }
}
