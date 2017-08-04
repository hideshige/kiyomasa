<?php
/**
 * JSON用 共通モデル
 * 
 * @author   Sawada Hideshige
 * @version  1.0.0.0
 * @package  extension
 * 
 */

namespace Dh\Ai\Extension;

use Php\Framework\Device as D;
use Yourname\Yourproject\Prop as P;

abstract class JsonModel implements P\Ajax
{
    /**
     * プログラムを実行する
     * @return void
     */
    abstract protected function execute(): void;
    
    /**
     * スローされた先のプログラム
     * @param string $mess スローメッセージ
     */
    abstract protected function throwCatch(string $mess): bool;
    
    /**
     * 最後に実行するプログラム
     * @return void
     */
    abstract protected function finalLogic(): void;

    protected $json = []; // JSON用の配列

    /**
     * プログラム実行のためのロジック
     * @return bool FALSEの場合エラーページを表示する
     */
    public function __construct()
    {
        // JSONフラグを真に
        D\S::$jflag = true;
    }
    
    /**
     * プログラム実行のためのロジック
     * @return array
     */
    public function logic(): array
    {
        try {
            $this->execute();
        } catch (D\UserException $e) {
            D\S::$dbm->rollback();
            $this->throwCatch($e->getMessage());
        } catch (\Error $e) {
            $info = new D\ErrorInfo;
            $info->set($e->getMessage(), $e->getFile(), $e->getLine());
            $mess =  'エラーになりました';
            $this->throwCatch($mess);
        } finally {
            $this->finalLogic();
            return $this->json;
        }
    }
}
