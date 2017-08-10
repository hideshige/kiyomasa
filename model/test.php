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
        $this->recursive(2, 'B', 'E', 'W', $i);
        
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
        // 円盤が2個の場合、動作は3回で済む
        // 2個以上の円盤をひとつの塊として考えると再帰呼び出しができる
        // すなわち、移動回数hanoi(n)は円盤の数をnとすると以下の式になる
        // hanoi(n) = if (n > 1) {hanoi(n - 1) + 1 + hanoi(n - 1)} else {1}
        
        $i ++;
        dump($i, $n);
        if ($n > 1) {
            // 上の一塊をいったん開始ポールから作業ポールへ移動させる
            $this->recursive($n - 1, $begin, $work, $end, $i);
            // 次に下の円盤を開始ポールから終了ポールへ移動させる
            dump('(' . $n .')' . $begin . ' → ' . $end);
            // 上の一塊を作業ポールから終了ポールへ移動させる
            $this->recursive($n - 1, $work, $end, $begin, $i);
        } else {
            // 1個だけのときは開始ポールから終了ポールへ1回移動するだけ
            dump('[' . $n .']' . $begin . ' → ' . $end);
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

