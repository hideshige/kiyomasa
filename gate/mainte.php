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
    public $tpl = ['part/header', 'mainte', 'part/footer'];

    /**
     * 実行
     * @return bool
     */
    public function execute(): bool
    {
        if (D\S::$url[0] == 'mainte') {
            header('Location: /');
            exit(0);
        }
        if (!isset(S::$user['user_id'])) {
            echo 'ただいまメンテナンス中です';
            exit(0);
        }
        return true;
    }
}
