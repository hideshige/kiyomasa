<?php
/**
 * JSON用 共通モデル
 * 
 * @author   Sawada Hideshige
 * @version  1.0.0.0
 * @package  extension
 * 
 */

namespace Yourname\Yourproject\Extension;

use Php\Framework\Device as D;

abstract class JsonModel
{
    /**
     * プログラムを実行する
     */
    abstract protected function execute();
    
    /**
     * スローされた先のプログラム
     * @param string $mess スローメッセージ
     */
    abstract protected function throwCatch($mess);
    
    /**
     * 最後に実行するプログラム
     */
    abstract protected function finalLogic();

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
    public function logic()
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
