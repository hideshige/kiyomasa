<?php
/**
 * サンプル シェル
 *
 * @author   Sawada Hideshige
 * @version  1.0.0.0
 * @package  shell
 */

namespace Yourname\Yourproject\Shell;

use Yourname\Yourproject\Work as W;

class Sample extends W\BaseShell
{
    /**
     * 実行
     * @global array $argv
     * @return void
     */
    protected function execute(): void
    {
        global $argv;

        var_dump($argv);
        echo 'TEST';
    }
}
