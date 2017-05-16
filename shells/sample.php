<?php
/**
 * サンプル シェル
 *
 * @author   Hideshige Sawada
 * @version  1.0.0.0
 * @package  shells
 */

namespace Bunroku\Kiyomasa\Shells;

use Bunroku\Kiyomasa\Device\Log;

class Sample
{
    public function logic()
    {
        Log::$batch = 'batch/';
        global $argv;

        echo 'TEST';
    }
}
