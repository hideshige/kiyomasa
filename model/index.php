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
    public $tpl = ['part/header', 'index', 'part/footer'];

    /**
     * 実行
     * @return void
     */
    protected function execute(): void
    {
        E\Citadel::set('YOURSITE');
    }
    
    /**
     * 例外処理
     * @param string $mes
     * @return bool
     */
    protected function throwCatch(string $mes): bool
    {
        $this->tpl[1] = 'content/error_page';
        D\S::$disp[1]['MESSAGE'][0]['message'] = $mes;
        return true;
    }
}
