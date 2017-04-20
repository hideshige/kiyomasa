<?php
/**
 * index モデル
 *
 * @author   Hideshige Sawada
 * @version  1.0.0.0
 */
 
class index {
  public $tpl = array('header', 'index', 'footer');
  public $equipment = array();

  public function logic() {
    citadel::set('sample');
  }
}
