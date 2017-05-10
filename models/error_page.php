<?php
/**
 * error モデル
 *
 * @author   Hideshige Sawada
 * @version  1.0.0.0
 */

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
        } catch (Exception $e) {
        } finally {
        }
    }
}
