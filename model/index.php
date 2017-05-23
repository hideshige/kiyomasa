<?php
/**
 * index モデル
 *
 * @author   Sawada Hideshige
 * @version  1.0.0.0
 * @package  models
 */

namespace Yourname\Yourproject\Model;

use Php\Framework\Device as D;
use Yourname\Yourproject\Extension as E;

class Index extends E\BaseModel
{
    public $tpl = ['header', 'index', 'footer'];

    protected function execute()
    {
        E\Citadel::set(FROM_NAME);
    }
    
    protected function throwCatch($mes)
    {
        $this->tpl[1] = 'error_page';
        D\S::$disp[1]['MESSAGE'][0]['message'] = $mes;
        return true;
    }
}
