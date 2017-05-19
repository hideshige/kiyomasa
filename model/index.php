<?php
/**
 * index モデル
 *
 * @author   Hideshige Sawada
 * @version  1.0.0.0
 * @package  models
 */

namespace Yourname\Yourproject\Model;

use Php\Framework\Kiyomasa\Device as D;
use Yourname\Yourproject\Extension as E;

class Index
{
    public $tpl = ['header', 'index', 'footer'];

    public function logic()
    {
        try {
            E\Citadel::set(FROM_NAME);
            
            
        } catch (E\FwException $e) {
            $mes = $e->getMessage();
            D\Log::error($mes);
            dump($mes);
        } finally {
        }
    }
}
