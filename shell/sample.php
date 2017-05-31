<?php
/**
 * サンプル シェル
 *
 * @author   Sawada Hideshige
 * @version  1.0.0.0
 * @package  shell
 */

namespace Yourname\Yourproject\Shell;

use Php\Framework\Devic as D;
use Yourname\Yourproject\Extention as E;

class Sample extends E\BaseShell
{
    public function execute(): void
    {
        global $argv;

        var_dump($argv);
        echo 'TEST';
    }
}
