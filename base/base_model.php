<?php
/**
 * HTML表示用 抽象クラス
 * 
 * @author   Sawada Hideshige
 * @version  1.0.0.0
 * @package  base
 * 
 */

namespace Yourname\Yourproject\Base;

use Php\Framework\Device as D;
use Yourname\Yourproject\Prop as P;

abstract class BaseModel implements P\Html
{
    /**
     * プログラムを実行する
     * @return void
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
        } catch (D\UserEx $e) {
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
