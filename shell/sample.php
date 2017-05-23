<?php
/**
 * サンプル シェル
 *
 * @author   Sawada Hideshige
 * @version  1.0.0.0
 * @package  shell
 */

namespace Yourname\Yourproject\Shell;

use Php\Framework\Device\Log;

class Sample
{
    public function logic()
    {
        Log::$batch = 'batch/';
        global $argv;

        echo 'TEST';
    }
}
