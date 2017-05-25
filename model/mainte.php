<?php
/**
 * error モデル
 *
 * @author   Sawada Hideshige
 * @version  1.0.0.0
 * @package  models
 */

namespace Yourname\Yourproject\Model;

use Php\Framework\Device as D;
use Yourname\Yourproject\Extension as E;

class Mainte
{
    public $tpl = ['header', 'mainte', 'footer'];

    public function logic()
    {
        if (D\S::$url[0] == 'mainte') {
            header('Location: /');
            exit;
        }
        $title = 'メンテナンス中';
        E\Citadel::set($title, false, false);
        if (!isset(S::$user['user_id'])) {
            echo 'ただいまメンテナンス中です';
            exit;
        }
    }
}
