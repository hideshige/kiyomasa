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
use Yourname\Yourproject\Work as W;
use Yourname\Yourproject\Prop as P;

class Mainte implements P\Html
{
    public $tpl = ['part/header', 'mainte', 'part/footer'];

    /**
     * ロジック
     * @return bool
     */
    public function logic(): bool
    {
        if (D\S::$url[0] == 'mainte') {
            header('Location: /');
            exit;
        }
        $title = 'メンテナンス中';
        W\Citadel::set($title, false, false);
        if (!isset(S::$user['user_id'])) {
            echo 'ただいまメンテナンス中です';
            exit;
        }
        return true;
    }
}
