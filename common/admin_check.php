<?php
/**
 * admin用のページかどうかチェックする共通モデル
 *
 * @author   Hideshige Sawada
 * @version  1.0.2.0
 * @package  common
 *
 */

namespace kiyomasa;

class AdminCheck
{
    public function logic()
    {
        global $g_admin_flag;
        $g_admin_flag = true;

//        global $g_ip_address;
//        $g_ip_check = false;
//        if (!in_array(IP_ADDRESS, $g_ip_address)) {
//            // メンテ突破端末以外はエラー画面に
//            header(sprintf('Location: %serror', DOMAIN_NAME));
//            exit;
//        }

        if (filter_input(INPUT_SERVER, 'SERVER_PORT') != '443') {
            // httpはhttpsに飛ばす
            header(sprintf('Location: %sadmin', SSL_LINK_DOMAIN_NAME));
            exit;
        }
    
        if (!isset ($_SESSION['user_id']) or $_SESSION['user_id'] != 1) {
            // 管理者以外は通常のエラー画面に
            header(sprintf('Location: %serror', DOMAIN_NAME));
            exit;
        }
    }

}


