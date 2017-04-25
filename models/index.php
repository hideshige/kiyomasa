<?php
/**
 * index モデル
 *
 * @author   Hideshige Sawada
 * @version  1.0.0.0
 */
 
class index {
  public $tpl = array('header', 'index', 'footer');

  public function logic() {
    citadel::set('sample');
  }
}
