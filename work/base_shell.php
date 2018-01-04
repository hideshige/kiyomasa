<?php
/**
 * シェル用 抽象クラス
 * 
 * @author   Sawada Hideshige
 * @version  1.0.0.0
 * @package  work
 * 
 */

namespace Yourname\Yourproject\Work;

use Yourname\Yourproject\Prop as P;

abstract class BaseShell implements P\Shell
{
    /**
     * プログラムを実行する
     * @return void
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
