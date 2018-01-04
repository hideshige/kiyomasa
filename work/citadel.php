<?php
/**
 * シタデル　共通モデル
 * 
 * @author   Sawada Hideshige
 * @version  1.0.0.0
 * @package  work
 * 
 * 自由に読み出せる静的便利機能をここに記述する
 * 
 */

namespace Yourname\Yourproject\Work;

use Php\Framework\Device as D;

class Citadel
{
    /**
     * デフォルト値のセットとセッションまわりの確認
     * @param string $title
     * @param bool $login_flag ログインしていないユーザーをログイン画面に飛ばす場合TRUE
     * @param bool $token_update_flag トークンを強制的にアップデートしない場合FALSE
     * @return void
     */
    public static function set(
        string $title = '',
        bool $login_flag = true,
        bool $token_update_flag = true
    ): void {
        self::titleSet($title);
        for ($i = 0; $i < 3; $i ++) {
            D\S::$disp[$i]['REPLACE']['domain'] = DOMAIN_NAME;
            D\S::$disp[$i]['REPLACE']['link_domain'] = LINK_DOMAIN_NAME;
        }
        //セッションまわりの処理をここに記入する
    }
    
    /**
     * タイトルのセット
     * @param string $title
     * @return void
     */
    public static function titleSet(string $title): void
    {
        if ($title) {
            for ($i = 0; $i < 3; $i ++) {
                D\S::$disp[$i]['REPLACE']['title'] = $title;
            }
        }
    }
}
