<?php
/**
 * index モデル
 *
 * @author   Hideshige Sawada
 * @version  1.0.0.0
 * @package  models
 */

namespace kiyomasa;

class Index
{
    public $tpl = ['header', 'index', 'footer'];

    public function logic()
    {
        try {
            Citadel::set('sample');
        } catch (Exception $e) {
        } finally {
        }
    }
}
