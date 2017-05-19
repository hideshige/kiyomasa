<?php
/**
 * index モデル
 *
 * @author   Hideshige Sawada
 * @version  1.0.0.0
 * @package  model
 */

namespace Yourname\Yourproject\Model;

use Yourname\Yourproject\Extension as E;

class Index
{
    public $tpl = ['header', 'index', 'footer'];

    public function logic()
    {
        E\Citadel::set(FROM_NAME);
    }
}

