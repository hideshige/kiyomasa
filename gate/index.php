<?php
/**
 * index 
 *
 * @author   Sawada Hideshige
 * @version  1.0.0.0
 * @package  gate
 */

namespace Yourname\Yourproject\Gate;

use Php\Framework\Device as D;

class Index
{
    public array $tpl = ['part/header', 'index', 'part/footer'];

    /**
     * 実行
     * @return bool
     */
    public function execute(): bool
    {
        try {
        } catch (D\UserEx $e) {
        }
        return true;
    }
}
