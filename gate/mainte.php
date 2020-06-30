<?php
/**
 * メンテ 
 *
 * @author   Sawada Hideshige
 * @version  1.0.0.0
 * @package  gate
 */

namespace Yourname\Yourproject\Gate;

class Mainte
{
    public array $tpl = ['part/header', 'mainte', 'part/footer'];

    /**
     * 実行
     * @return bool
     */
    public function execute(): bool
    {
        return true;
    }
}
