<?php
/**
 * error 
 *
 * @author   Sawada Hideshige
 * @version  1.0.0.0
 * @package  gate/content
 */

namespace Yourname\Yourproject\Gate\Content;

use Php\Framework\Device as D;
use Yourname\Yourproject\Work as W;
use Yourname\Yourproject\Prop as P;

class ErrorPage implements P\Html
{
    public $tpl = ['part/header', 'content/error_page', 'part/footer'];

    /**
     * ロジック
     * @return bool
     */
    public function logic(): bool
    {
        $title = 'エラー';
        W\Citadel::set($title, false, false);
        $message = 'ページが見つかりません。';
        if (isset($_SESSION['error_message']) and $_SESSION['error_message']) {
            $message = $_SESSION['error_message'];
            unset($_SESSION['error_message']);
        }
        D\S::$disp[1]['MESSAGE'][0]['message'] = $message;
        return true;
    }
}
