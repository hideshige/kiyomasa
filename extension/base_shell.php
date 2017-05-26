<?php
/**
 * シェル用 基本モデル
 * 
 * @author   Sawada Hideshige
 * @version  1.0.0.0
 * @package  extension
 * 
 */

namespace Yourname\Yourproject\Extension;

abstract class BaseShell implements D\ShellProp
{
    /**
     * プログラムを実行する
     */
    abstract protected function execute(): void;
    
    /**
     * プログラム実行のためのロジック
     * @return bool FALSEの場合エラーページを表示する
     */
    public function logic(): bool
    {
        try {
            $check = true;
            $this->execute();
        } catch (\Error $e) {
            echo $e->getMessage();
            $check = false;
        } finally {
            return $check;
        }
    }
}
