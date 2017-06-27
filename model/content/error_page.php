<?php
/**
 * error モデル
 *
 * @author   Sawada Hideshige
 * @version  1.0.0.0
 * @package  content
 */

namespace Yourname\Yourproject\Model\Content;

use Php\Framework\Device as D;
use Yourname\Yourproject\Extension as E;

class ErrorPage implements D\HtmlProp
{
    public $tpl = ['part/header', 'content/error_page', 'part/footer'];

    public function logic(): bool
    {
        $title = 'エラー';
        E\Citadel::set($title, false, false);
        $message = 'ページが見つかりません。';
        if (isset($_SESSION['error_message']) and $_SESSION['error_message']) {
            $message = $_SESSION['error_message'];
            unset($_SESSION['error_message']);
        }
        D\S::$disp[1]['MESSAGE'][0]['message'] = $message;
        return true;
    }
}
