<?php
/**
 * テスト モデル
 *
 * @author   Sawada Hideshige
 * @version  1.0.0.0
 * @package  model
 */

namespace Yourname\Yourproject\Model;

use Php\Framework\Device as D;
use Yourname\Yourproject\Extension as E;

class Test extends E\BaseModel
{
    public $tpl = ['part/header', 'content/error_page', 'part/footer'];

    /**
     * 実行
     * @return void
     */
    protected function execute(): void
    {
        if (ENV >= 2) {
            // 検証環境以外は通常のエラー画面を表示
            throw new D\UserEx();
        }
        E\Citadel::set('test');
        
        // 再帰関数の理解
        $i = 0;
        $this->recursive(2, 'A', 'C', 'B', $i);
        
        //$this->benchmark();
        return;
    }
    
    /**
     * 例外処理
     * @param string $mes
     * @return bool
     */
    protected function throwCatch(string $mes): bool
    {
        return false;
    }
    
    /**
     * ハノイの塔の攻略手順
     * @param int $n 円盤の数
     * @param string $begin 開始ポール
     * @param string $end 終了ポール
     * @param string $work 作業ポール
     * @param int $i 関数実行番号
     * @return void
     */
    private function recursive(
        int $n,
        string $begin,
        string $end,
        string $work,
        int &$i
    ): void {
        $i ++;
        dump($i, $n);
        if ($n > 0) {
            $this->recursive($n - 1, $begin, $work, $end, $i);
            dump('(' . $n .')' . $begin . ' → ' . $end);
            $this->recursive($n - 1, $work, $end, $begin, $i);
        }
    }
    
    /**
     * ベンチマークテスト
     * @return void
     */
    private function benchmark(): void
    {
        $time_a = microtime(true);
        for ($i = 0; $i < 100000000; $i ++) {
            // 測定したいこと
        }
        $time_b = microtime(true);
        $time = $time_b - $time_a;
        dump($time);
    }
}

