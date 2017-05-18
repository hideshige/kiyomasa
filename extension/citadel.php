<?php
/**
 * シタデル　共通モデル
 * 
 * @author   Hideshige Sawada
 * @version  1.0.0.0
 * @package  extension
 * 
 * 自由に読み出せるスタティックな便利機能をここに記述する
 * 
 */

namespace Yourname\Yourproject\Extension;

use Php\Framework\Kiyomasa\Core\S;

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
            S::$disp[$i]['REPLACE']['title'] = $title;
            S::$disp[$i]['REPLACE']['domain'] = DOMAIN_NAME;
            S::$disp[$i]['REPLACE']['link_domain'] = LINK_DOMAIN_NAME;
        }
        //セッションまわりの処理をここに記入する
    }

    /**
     * サニタイズした文字を戻す
     * @param string $word 戻したい文字
     * @return string 戻した文字
     */
    public static function hDecode($word)
    {
        $word2 = htmlspecialchars_decode($word, ENT_QUOTES);
        $res = $word2;
        global $g_change_chara;
        if ($g_change_chara) {
            foreach ($g_change_chara as $ck => $cv) {
                $res = str_replace($ck, $cv, $word2);
            }
        }
        return $res;
    }

    /**
     * フォームのトークンの確認
     */
    public static function checkFormToken()
    {
        if (!isset(S::$post['token']) or !isset($_SESSION['token'])
            or S::$post['token'] != $_SESSION['token']) {
            throw new FwException('Token Error');
        }
    }
}

