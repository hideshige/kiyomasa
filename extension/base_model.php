<?php
/**
 * HTML表示用 基本モデル
 * 
 * @author   Hideshige Sawada
 * @version  1.0.0.0
 * @package  extension
 * 
 */

namespace Yourname\Yourproject\Extension;

use Php\Framework\Device as D;

abstract class BaseModel
{
    /**
     * プログラムを実行する
     */
    abstract protected function execute();
    
    /**
     * スローされた先のプログラム
     * @param string $mes スローメッセージ
     * @return boolean
     */
    abstract protected function throwCatch($mes);
    
    /**
     * プログラム実行のためのロジック
     * @return boolean FALSEの場合エラーページを表示する
     */
    public function logic() {
        try {
            $check = true;
            $this->execute();
        } catch (D\UserException $e) {
            D\S::$dbm->rollback();
            $check = $this->throwCatch($e->getMessage());
        } catch (D\SystemException $e) {
            D\S::$dbm->rollback();
            $mes = $e->getMessage();
            // エラー内容をログに記録
            D\Log::error($mes);
            // デバッグ表示（本番環境には表示されない）
            dump($mes);
            $disp_mes = 'システムエラー　' . TIMESTAMP;
            $_SESSION['error_message'] = $disp_mes;
            $check = false;
        } finally {
            return $check;
        }
    }
}

