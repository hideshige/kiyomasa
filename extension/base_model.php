<?php
/**
 * HTML表示用 基本モデル
 * 
 * @author   Sawada Hideshige
 * @version  1.0.0.0
 * @package  extension
 * 
 */

namespace Yourname\Yourproject\Extension;

use Php\Framework\Device as D;

abstract class BaseModel implements D\HtmlProp
{
    /**
     * プログラムを実行する
     */
    abstract protected function execute(): void;
    
    /**
     * スローされた先のプログラム
     * @param string $mes スローメッセージ
     * @return bool
     */
    abstract protected function throwCatch(string $mes): bool;
    
    /**
     * プログラム実行のためのロジック
     * @return bool FALSEの場合エラーページを表示する
     */
    public function logic(): bool
    {
        try {
            $check = true;
            $this->execute();
        } catch (D\UserException $e) {
            D\S::$dbm->rollback();
            $check = $this->throwCatch($e->getMessage());
        } catch (\Error $e) {
            $info = new D\ErrorInfo;
            $info->set($e->getMessage(), $e->getFile(), $e->getLine());
            $_SESSION['error_message'] = 'システムエラー ' . TIMESTAMP;
            $check = false;
        } finally {
            return $check;
        }
    }
}
