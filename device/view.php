<?php
/**
 * VIEW関連クラス
 *
 * テンプレートの<!-- BEGEIN *** -->～{???}～<!-- END *** --->
 * の{???}を$txtに置き換える仕組み。
 * S::$disp[テンプレート番号]['***'][データ繰り返し番号]['???'] = $txt;
 * として指定する。
 * データ繰り返し番号$nが1以上の場合、$n+1回繰り返し表示する。
 *
 * S::$disp[テンプレート番号]['REPLACE']['???'] = $txt;
 * として指定した場合、<!-- BEGEIN *** -->～<!-- END *** --->を無視して
 * テンプレートの{???}に$txtが置き換えられる。
 *
 * <!-- INCLUDE *** -->には指定のテンプレートが挿入される。
 * 
 * 携帯の場合、$_SESSION['mobile_pc_flag']で携帯とPCの表示切り替えができる
 * 
 * @author   Sawada Hideshige
 * @version  1.1.7.2
 * @package  device
 * 
 */

namespace Php\Framework\Device;

class View
{
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
                $content = self::setContent($open, $disp);
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
     * テキストにデータを埋め込む
     * @param string $content テキスト内容
     * @param array $disp テンプレートに埋め込むデータ配列
     * @return string
     */
    public static function setContent(string $content, array $disp): string
    {
        return self::match(
            $disp, self::replace($disp, self::elementMatch($content)));
    }

    /**
     * テンプレートの読み込み
     * @param string $tpl テンプレートファイル名
     * @return string 読み込んだコンテンツ
     * @throws \Error
     */
    public static function open(string $tpl): string
    {
        $content = '';
        $add = strpos($tpl, '.') !== false ? '' : '.tpl';
        $tpl_folder = (MOBILE_FLAG and !isset($_SESSION['mobile_pc_flag']))
            ? 'template_mobile/' : 'template/';
        $fname = SERVER_PATH . $tpl_folder . $tpl . $add;
        if ($tpl_folder === 'template_mobile/' and !file_exists($fname)) {
              $fname = SERVER_PATH . 'template/' . $tpl . $add;
        }
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
        preg_match_all('/<!-- INCLUDE (.*?) -->/', $content, $element_match);

        if ($element_match[1]) {
            $element_match[1] = array_unique($element_match[1]);
            foreach ($element_match[1] as $ek => $element_name) {
                $element = self::open($element_name);
                $content = str_replace($element_match[0][$ek],
                    $element, $content);
            }
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
    private static function replace(array &$disp, string $content): string
    {
        if (isset($disp['REPLACE'])) {
            foreach ($disp['REPLACE'] as $k => $v) {
                $content = str_replace('{' . $k . '}', $v, $content);
            }
            unset($disp['REPLACE']);
        }
        return $content;
    }

    /**
     * テンプレートから埋め込み部分を検索
     * @param array|string $disp テンプレートに埋め込むデータ
     * @param string $content テンプレート
     * @return void
     */
    private static function match($disp, string $content): string
    {
        $match = $match1 = $match2 = [];
        preg_match_all('/<!-- BEGIN (.*?) -->/', $content, $match);

        foreach ($match[1] as $name) {
            $pattern = '/<!-- BEGIN ' . $name
                . ' -->(.*)<!-- END ' . $name . ' -->/s';
            preg_match_all($pattern, $content, $match1);
            $tag_data = $original = isset($match1[1][0]) ? $match1[1][0] : '';

            preg_match_all('/{(.*?)}/', $original, $match2);

            if (isset($disp[$name])) {
                $content = self::matchSet(
                    $content, $original, $disp, $name, $tag_data, $match2);
            } else if (isset($match1[0][0])) {
                $content = str_replace($match1[0][0], '', $content);
            }
        }
        return $content;
    }
    
    /**
     * テンプレートの埋め込み
     * @param string $content
     * @param string $original
     * @param array $disp
     * @param string $name
     * @param string $tag_data
     * @param array $match2
     * @return string
     */
    private static function matchSet(
        string $content,
        string $original,
        array $disp,
        string $name,
        string $tag_data,
        array $match2
    ): string {
        $all_tag = '';
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
        $original = '<!-- BEGIN ' . $name . ' -->'
            . $original . '<!-- END ' . $name . ' -->';
        return str_replace(['<!-- BEGIN ' . $name . ' -->',
            '<!-- END ' . $name . ' -->'], '',
            str_replace($original, $all_tag, $content));
    }
    
    /**
     * {}部のパラメーターの挿入
     * @param array $match 検索結果
     * @param array|string $disp 表示データ
     * @param string $tag_data タグデータ 参照渡し
     * @return void
     */
    private static function matchSetParam(
        array $match,
        $disp,
        string &$tag_data
    ): void {
        foreach ($match as $data) {
            if (isset($disp[$data])) {
                $change_data = $disp[$data];
            } else if (preg_match('/[ ,:;=]/s', $data)) {
                // JSデータの{}は変更しない
                $change_data = '{' . $data . '}';
            } else {
                $change_data = '';
            }
            $tag_data = str_replace('{' . $data . '}', $change_data, $tag_data);
        }
    }
}
