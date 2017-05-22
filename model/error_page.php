<?php
/**
 * error モデル
 *
 * @author   Hideshige Sawada
 * @version  1.0.0.0
 * @package  models
 */

namespace Yourname\Yourproject\Model;

use Php\Framework\Device as D;
use Yourname\Yourproject\Extension as E;

class ErrorPage
{
    public $tpl = ['header', 'error_page', 'footer'];

    public function logic()
    {
        $title = 'エラー';
        E\Citadel::set($title);
        $message = 'ページが見つかりません。';
        if (isset($_SESSION['error_message'])) {
            $message = $_SESSION['error_message'];
            unset($_SESSION['error_message']);
        }
        D\S::$disp[1]['MESSAGE'][0]['message'] = $message;
    }
}
