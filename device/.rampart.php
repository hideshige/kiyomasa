<?php
/**
 * ウォール　フレームワーク固有のインターフェース
 *
 * @author   Sawada Hideshige
 * @version  1.0.0.0
 * @package  device
 * 
 */

namespace Php\Framework\Device;

/**
 * HTMLモデル用インターフェース
 */
interface HtmlProp
{
    public function logic(): bool;
}

/**
 * Ajaxモデル用インターフェース
 */
interface AjaxProp
{
    public function logic(): array;
}

/**
 * シェル用インターフェース
 */
interface ShellProp
{
    public function logic(): bool;
}
