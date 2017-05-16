<?php
/**
 * index モデル
 *
 * @author   Hideshige Sawada
 * @version  1.0.0.0
 * @package  models
 */

namespace bunroku\kiyomasa\models;

use bunroku\kiyomasa\extension\Citadel;

class Index
{
    public $tpl = ['header', 'index', 'footer'];

    public function logic()
    {
        Citadel::set(FROM_NAME);
    }
}

