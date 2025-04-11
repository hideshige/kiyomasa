<?php
/**
 * VIEW関連クラス
 *
 * テンプレートの<!-- BEGEIN *** -->～{???}～<!-- END *** --->
 * の{???}を$txtに置き換える仕組み。
 * S::$disp['***'][データ繰り返し番号]['???'] = $txt;
 * として指定する。
 * データ繰り返し番号$nが1以上の場合、$n+1回繰り返し表示する。
 *
 * S::$disp['REPLACE']['???'] = $txt;
 * として指定した場合、<!-- BEGEIN *** -->～<!-- END *** --->を無視して
 * テンプレートの{???}に$txtが置き換えられる。
 * 
 * S::$disp['SHOW']['???'] = 1;
 * として指定した場合、<!-- BEGEIN ??? -->～<!-- END ??? --->を強制表示できる
 * 
 * {ht:???}とするとHTMLをサニタイズできる
 * {htbr:???}とするとHTMLをサニタイズしたあと改行を反映できる
 * {sl:???}とするとHTMLとJavaScriptをサニタイズできる
 * {url:???}とするとURLエンコードできる
 *
 * <!-- INCLUDE *** -->には指定のテンプレートが挿入される。
 * 
 * @author   Sawada Hideshige
 * @version  1.2.2.0
 * @package  device
 * 
 */

namespace Php\Framework\Device;

class View
{
    private static array $show = [];
    
    /**
     * 複数のテンプレートファイルを読み込みデータを埋め込んで表示させる
     * @param array $tpls
     * @param array $disp
     * @return string
     */
    public static function templates(array $tpls, array $disp): string
    {
        $contents = '';
        if ($tpls) {
            foreach ($tpls as $v) {
                $contents .= self::template($v, $disp);
            }
        }
        return $contents;
    }
    
    /**
     * テンプレートファイルを読み込みデータを埋め込んで表示させる
     * @param string $tpl ファイル名
     * @param array $disp テンプレートに埋め込むデータ配列
     * @return string
     */
    public static function template(string $tpl, array $disp): string
    {
        try {
            $content = '';
            $open = self::open($tpl);
            if ($open) {
                self::showDisp($disp);
                $content =
                    self::cleanVariable(
                    self::match($disp,
                    self::replace($disp, 
                    self::elementMatch($open))));
            }
        } catch (\Error $e) {
            $info = new ErrorInfo;
            $info->set($e->getMessage(), $e->getFile(), $e->getLine());
            $content = '';
        } finally {
            return $content;
        }
    }
    
    /**
     * テンプレートの強制表示
     * @param array &$disp
     * @return void
     */
    private static function showDisp(array &$disp): void
    {
        if (
            isset($disp['SHOW'])
            && is_array($disp['SHOW'])
            && $disp['SHOW']
        ) {
            self::$show = $disp['SHOW'];
            unset($disp['SHOW']);
        }
    }
    
    /**
     * 値の入っていない{}を一掃する
     * @param string $content
     * @return string
     */
    private static function cleanVariable(string $content): string
    {
        $script = [];
        $match1 = $match2 = $match3 = [];
        
        // スクリプト部の取得
        $pattern = '/<script(.*?)<\/script>/s';
        preg_match_all($pattern, $content, $match1);
        if (isset($match1[1]) && $match1[1]) {
            foreach ($match1[1] as $v) {
                preg_match_all('/{(.*?)}/', $v, $match2);
                if (isset($match2[1]) && $match2[1]) {
                    foreach ($match2[1] as $v) {
                        $script[$v] = 1;
                    }
                }
            }
        }
        
        // 全体の取得
        preg_match_all('/{(.*?)}/', $content, $match3);
        if (isset($match3[1]) && $match3[1]) {
            foreach ($match3[1] as $k => $v) {
                // スクリプトを除外
                if (!self::checkCode($v) && !isset($script[$v])) {
                    $content = str_replace($match3[0][$k], '', $content);
                }
            }
        }
        return $content;
    }

    /**
     * テンプレートの読み込み
     * @param string $tpl テンプレートファイル名
     * @return string 読み込んだコンテンツ
     * @throws \Error
     */
    private static function open(string $tpl): string
    {
        $content = '';
        $add = strpos($tpl, '.') !== false ? '' : '.tpl';
        $fname = SERVER_PATH . 'template/' . $tpl . $add;
        if (!file_exists($fname)) {
            throw new \Error('No Template ' . $fname);
        }
        $fh = fopen($fname, 'r');
        if ($fh === false) {
            throw new \Error('Template Open Error');
        } else {
            $content = fread($fh, max(1, filesize($fname)));
        }
        fclose($fh);
        return $content;
    }
    
    /**
     * インクルード要素の処理
     * @param string $content テンプレート
     * @return string
     */
    private static function elementMatch(string $content): string
    {
        //部分テンプレートの挿入
        $element_match = [];
        preg_match_all('/<!--\sINCLUDE\s(.*?)\s-->/', $content, $element_match);
        
        if ($element_match[1]) {
            $element_match[1] = array_unique($element_match[1]);
            foreach ($element_match[1] as $ek => $element_name) {
                $element = self::open($element_name);
                $content = str_replace($element_match[0][$ek],
                    $element, $content);
            }
            // インクルードしたファイルの中に別のインクルードの指定がある場合の対処
            $content = self::elementMatch($content);
        }
        return $content;
    }

    /**
     * REPLACE部の置換
     * @param array $disp 表示データ 参照渡し
     * @param string $content
     * @return string
     */
    private static function replace(array $disp, string $content): string
    {
        if (isset($disp['REPLACE'])) {
            foreach ($disp['REPLACE'] as $k => $v) {
                $content = str_replace('{htbr:' . $k . '}', nl2br(htmlspecialchars($v ?? '')), $content);
                $content = str_replace('{ht:' . $k . '}', htmlspecialchars($v ?? ''), $content);
                $content = str_replace('{sl:' . $k . '}', htmlspecialchars(str_replace(["\n", "\r"], ' ', addslashes($v ?? '')), ENT_QUOTES), $content);
                $content = str_replace('{url:' . $k . '}', urlencode($v ?? ''), $content);
                $content = str_replace('{' . $k . '}', $v ?? '', $content);
            }
            unset($disp['REPLACE']);
        }
        return $content;
    }

    /**
     * テンプレートから埋め込み部分を検索
     * @param array $disp テンプレートに埋め込むデータ
     * @param string $content テンプレート
     * @return void
     */
    private static function match(array $disp, string $content): string
    {
        $match = $match1 = $match2 = [];
        preg_match_all('/<!--\sBEGIN\s(.*?)\s-->/', $content, $match);
        
        if (isset($match[1]) && $match[1]) {
            foreach ($match[1] as $name) {
                $pattern = '/<!--\sBEGIN\s' . $name . '\s-->(.*?)'
                    . '<!--\sEND\s' . $name . '\s-->/s';
                preg_match_all($pattern, $content, $match1);
                
                $tag_data = isset($match1[1][0]) ? $match1[1][0] : '';
                preg_match_all('/{(.*?)}/', $tag_data, $match2);
                
                if (isset($disp[$name])) {
                    $content = self::matchSet(
                        $content, $tag_data, $disp, $name, $match2);
                } elseif (
                    isset($match1[0][0])
                    && $match1[0][0]
                    && !isset(self::$show[$name])
                ) {
                    $content = str_replace($match1[0][0], '', $content);
                }
            }
        }
        return $content;
    }
    
    /**
     * テンプレートの埋め込み
     * @param string $content
     * @param string $tag_data
     * @param array $disp
     * @param string $name
     * @param string $tag_data
     * @param array $match2
     * @return string
     */
    private static function matchSet(
        string $content,
        string $tag_data,
        array $disp,
        string $name,
        array $match2
    ): string {
        $all_tag = '';
        $original = $tag_data;
        $num = is_array($disp[$name]) ? count($disp[$name]) : 0;
        for ($i = 0; $i < $num; $i ++) {
            if (isset($disp[$name][$i])) {
                $tag_data = self::match($disp[$name][$i], $tag_data);
            }
            if (isset($match2[1], $disp[$name][$i])) {
                self::matchSetParam($match2[1], $disp[$name][$i], $tag_data);
            }
            $all_tag .= $tag_data;
            $tag_data = $original;
        }
        return str_replace('<!-- BEGIN ' . $name . ' -->'
            . $tag_data . '<!-- END ' . $name . ' -->', $all_tag, $content);
    }
    
    /**
     * {}部のパラメーターの挿入
     * @param array $match 検索結果
     * @param array $disp 表示データ
     * @param string &$tag_data タグデータ 参照渡し
     * @return void
     */
    private static function matchSetParam(
        array $match,
        array $disp,
        string &$tag_data
    ): void {
        foreach ($match as $disp_data) {
            $data = str_replace(['htbr:', 'ht:', 'sl:', 'url:'], '', $disp_data);
            if (isset($disp[$data])) {
                if (str_contains($disp_data, 'ht:')) {
                    $change_data = htmlspecialchars($disp[$data]);
                } elseif (strstr($disp_data, 'htbr:')) {
                    $change_data = nl2br(htmlspecialchars($disp[$data]));
                } elseif (strstr($disp_data, 'sl:')) {
                    $change_data = htmlspecialchars(str_replace(["\n", "\r"],
                        ' ', addslashes($disp[$data])), ENT_QUOTES);
                } elseif (strstr($disp_data, 'url:')) {
                    $change_data = urlencode($disp[$data]);
                } else {
                    $change_data = $disp[$data];
                }
            } elseif (self::checkCode($disp_data)) {
                // JSデータの{}は変更しない
                $change_data = '{' . $disp_data . '}';
            } else {
                // 値が設定されていない{}は消す
                $change_data = '';
            }
            $tag_data = str_replace('{' . $disp_data . '}', $change_data, $tag_data);
        }
    }
    
    /**
     * コードかどうかの確認
     * @param string $string
     * @return bool
     */
    private static function checkCode(string $string): bool
    {
        return (preg_match("/[ ,'\".;=]/s", $string) || $string === '');
    }
}
