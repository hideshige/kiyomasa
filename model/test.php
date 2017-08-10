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

        return;
        
        // 再帰関数の理解
        $count = $this->recursive(3, '出発地', '目的地', '経由地');
        echo '移動は' . $count . '回<br />';
        
        $this->benchmark();
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
     * @param string $begin 出発地
     * @param string $end 目的地
     * @param string $via 経由地
     * @return int 移動回数
     */
    private function recursive(
        int $n,
        string $begin,
        string $end,
        string $via
    ): int {
        // 円盤が2個の場合、動作は3回で済む
        // 2個以上の円盤をひとつの塊として考えると再帰呼び出しができる
        // すなわち、移動回数hanoi(n)は円盤の数をnとすると以下の式になる
        // hanoi(n) = if (n >= 2) {hanoi(n - 1) + 1 + hanoi(n - 1)} else {1}
        
        $count = 0;
        if ($n >= 2) {
            // 上の一塊をいったん出発地から経由地へ移動させる
            $count += $this->recursive($n - 1, $begin, $via, $end);
            // 次に下の円盤を出発地から目的地へ移動させる
            $count ++;
            echo $n . ' 番を ' . $begin . ' から ' . $end . ' へ<br />';
            // 上の一塊を経由地から目的地へ移動させる
            $count += $this->recursive($n - 1, $via, $end, $begin);
        } else {
            // 1個だけのときは出発地から目的地へ1回移動するだけ
            $count ++;
            echo $n . ' 番を ' . $begin . ' から ' . $end . ' へ<br />';
        }
        return $count;
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

