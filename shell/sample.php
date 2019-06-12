<?php
/**
 * サンプル シェル
 *
 * @author   Sawada Hideshige
 * @version  1.0.0.0
 * @package  shell
 */

namespace Yourname\Yourproject\Shell;

class Sample
{
    /**
     * 実行
     * @return bool FALSEの場合エラーページを表示する
     */
    public function execute(): bool
    {
        try {
            $check = true;
            
            global $argv;
            var_dump($argv);
            
            echo 'TEST';
            
        } catch (\Error $e) {
            echo $e->getMessage();
            $check = false;
        } finally {
            return $check;
        }
    }
}
