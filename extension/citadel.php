<?php
/**
 * シタデル　共通モデル
 * 
 * @author   Sawada Hideshige
 * @version  1.0.0.0
 * @package  extension
 * 
 * 自由に読み出せるスタティックな便利機能をここに記述する
 * 
 */

namespace Yourname\Yourproject\Extension;

use Php\Framework\Device as D;

class Citadel
{
    /**
     * デフォルト値のセットとセッションまわりの確認
     * @param string $title
     * @param boolean $login_flag ログインしていないユーザーをログイン画面に飛ばす場合TRUE
     * @param boolean $token_update_flag トークンを強制的にアップデートしない場合FALSE
     */
    public static function set(
        $title = '',
        $login_flag = true,
        $token_update_flag = true
    ) {
        for ($i = 0; $i < 3; $i ++) {
            D\S::$disp[$i]['REPLACE']['title'] = $title;
            D\S::$disp[$i]['REPLACE']['domain'] = DOMAIN_NAME;
            D\S::$disp[$i]['REPLACE']['link_domain'] = LINK_DOMAIN_NAME;
        }
        //セッションまわりの処理をここに記入する
    }
}
