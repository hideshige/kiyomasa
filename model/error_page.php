<?php
/**
 * error モデル
 *
 * @author   Hideshige Sawada
 * @version  1.0.0.0
 * @package  models
 */

namespace Yourname\Yourproject\Model;

use Php\Framework\Kiyomasa\Device\FwException;
use Php\Framework\Kiyomasa\Device\S;
use Php\Framework\Kiyomasa\Device\Log;
use Yourname\Yourproject\Extension\Citadel;

class ErrorPage
{
    public $tpl = ['header', 'error_page', 'footer'];

    public function logic()
    {
        try {
            $title = 'エラー';
            Citadel::set($title);
            $message = 'ページが見つかりません。';
            if (isset($_SESSION['error_message'])) {
                $message = $_SESSION['error_message'];
                unset($_SESSION['error_message']);
            }
            dump($_COOKIE,null);
            S::$disp[1]['MESSAGE'][0]['message'] = $message;
        } catch (FwException $e) {
            Log::error($e->getMessage());
            dump($e->getMessage());
        } finally {
        }
    }
}
