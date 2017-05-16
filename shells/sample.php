<?php
/**
 * サンプル シェル
 *
 * @author   Hideshige Sawada
 * @version  1.0.0.0
 * @package  shells
 */

namespace bunroku\kiyomasa\shells;

use bunroku\kiyomasa\device\Log;

class Sample
{
    public function logic()
    {
        Log::$batch = 'batch/';
        global $argv;

        echo 'TEST';
    }
}
