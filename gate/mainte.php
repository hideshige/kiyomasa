<?php
/**
 * メンテ 
 *
 * @author   Sawada Hideshige
 * @version  1.0.0.0
 * @package  gate
 */

namespace Yourname\Yourproject\Gate;

use Php\Framework\Device as D;

class Mainte
{
    public array $tpl = ['part/header', 'mainte', 'part/footer'];

    /**
     * 実行
     * @return bool
     */
    public function execute(): bool
    {
        if (MODE !== MODE_MAINTE) {
            D\S::$header[] = 'Location: /';
        } else {
            D\S::$disp = [];
        }
        return true;
    }
}
