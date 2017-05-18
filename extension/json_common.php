<?php
/**
 * JSON　共通モデル
 * 
 * @author   Hideshige Sawada
 * @version  1.0.0.0
 * @package  extension
 * 
 */

namespace Yourname\Yourproject\Extension;

use Php\Framework\Kiyomasa\Core\S;

class JsonCommon
{
    protected $json = [];
    
    public function __construct() {
        // JSONフラグを真に
        S::$jflag = true;
    }
}

