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
     * @param string $mes スローメッセージ
     */
    abstract protected function throwCatch($mes);

    protected $json = []; // JSON用の配列

    /**
     * プログラム実行のためのロジック
     * @return boolean FALSEの場合エラーページを表示する
     */
    public function __construct() {
        // JSONフラグを真に
        D\S::$jflag = true;
    }
    
    public function logic() {
        try {
            $this->execute();
        } catch (D\UserException $e) {
            D\S::$dbm->rollback();
            $this->throwCatch($e->getMessage());
        } catch (D\SystemException $e) {
            D\S::$dbm->rollback();
            $mes = $e->getMessage();
            // ログに記録
            D\Log::error($mes);
            // デバッグ表示（本番環境には表示されない）
            dump($mes);
            $this->json[$this->place] = 'システムエラー ' . TIMESTAMP;
        } finally {
            return $this->json;
        }
    }    
}

