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
use \Error;

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
        } catch (Error $e) {
            D\SystemError::setInfo($e, 'エラー　' . TIMESTAMP);
            $check = false;
        } finally {
            return $check;
        }
    }
}
