<?php
/**
 * error モデル
 *
 * @author   Hideshige Sawada
 * @version  1.0.0.0
 * @package  models
 */

namespace bunroku\kiyomasa\models;

use bunroku\kiyomasa\device\FwException;
use bunroku\kiyomasa\extension\Citadel;

class ErrorPage
{
    public $tpl = ['header', 'error', 'footer'];

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
            S::$disp[1]['MESSAGE'][0]['message'] = $message;
        } catch (FwException $e) {
        } finally {
        }
    }
}
