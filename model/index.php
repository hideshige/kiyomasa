<?php
/**
 * index モデル
 *
 * @author   Hideshige Sawada
 * @version  1.0.0.0
 * @package  model
 */

namespace Yourname\Yourproject\Model;

use Yourname\Yourproject\Extension\Citadel;

class Index
{
    public $tpl = ['header', 'index', 'footer'];

    public function logic()
    {
        Citadel::set(FROM_NAME);
    }
}

