<?php
/**
 * error 
 *
 * @author   Sawada Hideshige
 * @version  1.0.0.0
 * @package  gate
 */

namespace Yourname\Yourproject\Gate;

use Php\Framework\Device as D;

class ErrorPage
{
    public array $tpl = ['part/header', 'error_page', 'part/footer'];

    /**
     * 実行
     * @return bool
     */
    public function execute(): bool
    {
        $message = 'ページが見つかりません。';
        if (isset($_SESSION['error_message']) && $_SESSION['error_message']) {
            $message = $_SESSION['error_message'];
            unset($_SESSION['error_message']);
        } else {
            header('HTTP/1.1 404 Not Found');
        }
        D\S::$disp['MESSAGE'][0]['message'] = $message;
        return true;
    }
}
