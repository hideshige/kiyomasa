<?php
/**
 * index モデル
 *
 * @author   Hideshige Sawada
 * @version  1.0.0.0
 * @package  models
 */

namespace Yourname\Yourproject\Models;

use Yourname\Yourproject\Extension\Citadel;

class Index
{
    public $tpl = ['header', 'index', 'footer'];

    public function logic()
    {
        Citadel::set(FROM_NAME);
    }
}

