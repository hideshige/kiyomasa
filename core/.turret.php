<?php
/**
 * タレット　土台強化部
 *
 * @author   Sawada Hideshige
 * @version  1.0.7.1
 * @package  core
 * 
 */

namespace Php\Framework\Core;

use Php\Framework\Device\{ErrorInfo, S, View, UserEx};

class Turret
{
    use Wall;
    
    // UNICODE不可視文字トリム
    private $invisible_utf8_codes = [
        '&#x00AD;','&#x2000;','&#x2001;','&#x2002;','&#x2003;','&#x2004;',
        '&#x2005;','&#x2006;','&#x2007;','&#x2008;','&#x2009;',
        '&#x200A;','&#x200B;','&#x200C;','&#x200D;','&#x200E;',
        '&#x200F;','&#x2028;','&#x2029;','&#x202A;','&#x202B;',
        '&#x202C;','&#x202D;','&#x202E;','&#x202F;','&#x205F;',
        '&#x2060;','&#x2061;','&#x2062;','&#x2063;','&#x2064;',
        '&#x2065;','&#x2066;','&#x2067;','&#x2068;','&#x2069;',
        '&#x206A;','&#x206B;','&#x206C;','&#x206D;','&#x206E;',
        '&#x206F;','&#x2322;','&#x2323;','&#x2800;','&#x3164;',
        '&#xA717;','&#xA718;','&#xA719;','&#xA71A;','&#xA720;','&#xA721;',
        '&#xFE00;','&#xFE01;','&#xFE02;','&#xFE03;','&#xFE04;',
        '&#xFE05;','&#xFE06;','&#xFE07;','&#xFE08;','&#xFE09;',
        '&#xFE0A;','&#xFE0B;','&#xFE0C;','&#xFE0D;','&#xFE0E;',
        '&#xFE0F;','&#xFEFF;','&#xFFF0;','&#xFFF1;','&#xFFF2;',
        '&#xFFF3;','&#xFFF4;','&#xFFF5;','&#xFFF6;','&#xFFF7;','&#xFFF8;',
    ];
    private $invisible_strs;
    
    private $debug = false; // デバッグモード
    private $error_flag = false; // 初回エラーかどうか（循環防止のため）
    
    /**
     * コンストラクタ
     * @param bool $debug
     */
    public function __construct(bool $debug)
    {
        $this->debug = $debug;
        $this->invisible_strs = array_map(
            function ($code) {
                return html_entity_decode($code, ENT_NOQUOTES, 'UTF-8');
            }, $this->invisible_utf8_codes);
    }
    
    /**
     * 実行し、ビューにデータを渡す
     * @param string $pagename 実行するページ名
     * @param string $folder フォルダ名
     * @return void
     */
    public function disp(string $pagename, string $folder = ''): void
    {
        try {
            $class_name = NAME_SPACE . '\Gate\\' . trim(
                str_replace(' ', '', ucwords(str_replace(
                ['_', '/'], [' ', '\\'], $folder . $pagename))));
            
            $this->gate($class_name);
        } catch (UserEx|\Error $e) {
            $this->gateError($e);
        }
    }
    
    /**
     * ゲートの実行
     * @param string $class_name
     * @return void
     * @throws \Error
     */
    private function gate(string $class_name): void
    {
        $gate = new $class_name;
        $res = $gate->execute();
        if ($res === false) {
            throw new \Error($class_name . ' logic notice', 10);
        }
        
        $tpl = $gate->tpl ?? [];
        
        global $g_cache_flag;
        if ($g_cache_flag) {
            header('Expires: ' . gmdate('D, d M Y H:i:s T', time() + 36000));
            header('Cache-Control: private, max-age=' . 36000);
            header('Pragma: cache');
        } else {
            header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
            header('Cache-Control: private, max-age=0');
            header('Pragma: no-cache');
        }
        
        if (S::$jflag === false and count($tpl)) {
            header('Content-Type: text/html; charset=UTF-8');
            foreach ($tpl as $tk => $tv) {
                echo View::template($tv, S::$disp[$tk] ?? []);
            }
        }
        if (S::$jflag === true and is_array($res)) {
            $this->jsonDebug($res);
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode($res,
                JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
        }
    }
    
    /**
     * ゲート実行例外エラー
     * @param UserEx|\Error $e
     * @return void
     */
    private function gateError($e): void
    {
        if ($e->getCode() !== 10) {
            $info = new ErrorInfo;
            $info->set($e->getMessage(), $e->getFile(), $e->getLine());
        }

        // エラーページの表示
        if (S::$jflag === false) {
            if ($this->error_flag === false) {
                // 循環防止のフラグ
                $this->error_flag = true;
                // エラー画面の読み込み
                $this->disp('error_page');
            } else {
                header('Content-Type: text/html; charset=UTF-8');
                echo 'エラーになりましたが、エラー画面が表示できませんでした';
            }
        } else {
            $json = ['alert' => 'エラー'];
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode($json);
        }
    }
    
    /**
     * 不可視文字の排除
     * @param array|string $data
     * @return array|string
     */
    public function trim($data)
    {
        if (is_array($data)) {
            foreach ($data as $k => $v) {
                $data[$k] = $this->trim($v);
            }
        } else {
            $data = str_replace($this->invisible_strs, '',
                // 改行コード以外のコントロールコードを排除
                preg_replace('/[\x00-\x09\x0B\x0C\x0E-\x1F\x7F]/', '', $data));
        }
        return $data;
    }
}
