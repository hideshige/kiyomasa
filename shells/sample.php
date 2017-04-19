<?php
/**
 * サンプル シェル
 *
 * @author   Hideshige Sawada
 * @version  1.0.0.0
 * @package  shells
 */

class sample {
  public $equipment = array ();

  public function logic() {
    log::$batch = 'batch/';
    global $argv;
    
    echo 'TEST';
  }
}
