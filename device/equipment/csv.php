<?php
/**
 * CSV モジュール
 *
 * @author   Sawada Hideshige
 * @version  1.2.4.1
 * @package  device/equipment
 */

namespace Php\Framework\Device\Equipment;

use Php\Framework\Device as D;

class Csv
{
    /**
     * CSVファイルから配列として取得
     * @param string $file CSVのファイルパス
     * @param string $encode エンコード
     * @param string $mojicode CSVファイルの文字コード
     * @return array 取得した配列
     */
    public static function csvFileToArray(
        string $file,
        string $encode = DEFAULT_CHARSET,
        string $mojicode = 'SJIS-win'
    ): array {
        setlocale(LC_ALL, 'ja_JP');
        $data = [];
        if (file_exists($file)) {
            //ファイルの取得
            $contents = file_get_contents($file);
            if ($encode !== $mojicode) {
                //文字コードの変換
                $contents = mb_convert_encoding($contents, $encode, $mojicode);
            }
            //ダブルクォートを一旦予約語:::QUQU:::と:::QU:::に退避させて無害化
            $data = self::putFile(str_replace(':::QU:::', '"', 
                htmlspecialchars(str_replace(':::QUQU:::', '"',
                    str_replace('"', ':::QU:::', str_replace(
                        '""', ':::QUQU:::', $contents))
                ), ENT_QUOTES)));
        }
        return $data;
    }
    
    /**
     * 一時ファイルに保存してCSVファイルを配列に変換
     * @param string $contents
     * @return array
     */
    private static function putFile(string $contents): array
    {
        $data = [];
        
        $f = tmpfile();
        fwrite($f, $contents);
        rewind($f);
        $i = 0;
        while ($array = self::fgetcsvReg($f)) {
            $num = count($array);
            for ($c = 0; $c < $num; $c ++) {
                // 空欄の意味の""も&quot;になってしまうため空欄に書き換える
                $data[$i][$c] = $array[$c] === '&quot;' ? '' : $array[$c];
            }
            if (!empty($array)) {
                $i ++;
            }
        }
        fclose($f);
        
        return $data;
    }

    /**
     * fgetcsv()の文字化けの問題を解消
     * @param object $handle ファイルポインタ
     * @return bool|array 結果
     */
    private static function fgetcsvReg(&$handle)
    {
        $d = ',';
        $e = '"';
        $line = '';
        $dummy = [];
        $csv_matches = [];
        $eof = false;
        $itemcnt = 0;
        while ($eof === false) {
            $this_line = fgets($handle);
            $itemcnt += preg_match_all('/' . $e . '/',
                preg_replace('/' . $e . $d . '(.*?)' . $e . '/',
                '', $this_line), $dummy);
            if ($itemcnt % 2 === 0) {
                $eof = true;
            }
            $line  .= $this_line;
        }
        $csv_line = preg_replace('/(?:\\r\\n|[\\r\\n])?$/', $d, trim($line));
        $csv_pattern = '/(' . $e . '[^' . $e . ']*(?:' . $e . $e
            . '[^' . $e . ']*)*' . $e . '|[^' . $d . ']*)' . $d . '/';
        preg_match_all($csv_pattern, $csv_line, $csv_matches);
        $csv_data = $csv_matches[1];
        for ($csv_i = 0; $csv_i < count($csv_data); $csv_i ++) {
            // 囲み文字を消す
            $csv_data[$csv_i] = preg_replace( '/^' . $e . '(.*)' . $e . '$/s',
                '$1', $csv_data[$csv_i]);
            // エスケープを解除
            $csv_data[$csv_i] = preg_replace('/' . $e . $e . '/',
                $e, $csv_data[$csv_i]);
        }
        $res = empty(trim($line)) ? false : $csv_data;
        return $res;
    }

    /**
     * 配列をCSVに変換
     * @param array $get_data 配列データ
     * @param bool $header 1行目を書き出すか否か
     * @param string $mojicode CSVファイルの文字コード
     * @param string $encode エンコード
     * @return string CSVデータ
     * @throws D\UserEx
     */
    public static function arrayToCsv(
        array $get_data,
        bool $header = true,
        string $mojicode = 'SJIS-win',
        string $encode = DEFAULT_CHARSET
    ) {
        if (!$get_data or sizeof($get_data) > CSV_MAX) {
            throw new D\UserEx(CSV_MAX . '件以内に絞り込んでください');
        }

        $csv_arr = [];

        foreach ($get_data as $k => $v) {
            if ($k === 0 and $header) {
                $tmp = array_keys($v);
                $csv_arr[] = '"' . implode('","', $tmp) . '"';
            }
            if (is_array($v)) {
                $csv_arr[] = '"' . implode('","', $v) . '"';
            }
        }
        $csv = Chara::hDecode(
            // 区切り文字がずれないように事前に"をエスケープする
            str_replace('&quot;', '""', implode("\n", $csv_arr))
        );
        if ($mojicode !== $encode) {
            $csv = mb_convert_encoding($csv, $mojicode, $encode);
        }
        return $csv;
    }

    /**
     * 配列をTSVに変換
     * @param array $get_data 配列データ
     * @param bool $header 1行目を書き出すか否か
     * @param string $mojicode CSVファイルの文字コード
     * @param string $encode エンコード
     * @return string TSVデータ
     * @throws D\UserEx
     */
    public static function arrayToTsv(
        $get_data,
        $header = true,
        $mojicode = DEFAULT_CHARSET,
        $encode = DEFAULT_CHARSET
    ) {
        if (!$get_data or sizeof($get_data) > CSV_MAX) {
            throw new D\UserEx(CSV_MAX . '件以内に絞り込んでください');
        }

        $tsv_arr = [];

        foreach ($get_data as $k => $v) {
            if ($k === 0 and $header) {
                $tmp = array_keys($v);
                $tsv_arr[] = implode("\t", $tmp) ;
            }
            if (is_array($v)) {
                $tsv_arr[] = implode("\t", $v);
            }
        }
        $tsv = Chara::hDecode(implode("\n", $tsv_arr));
        if ($mojicode !== $encode) {
            $tsv = mb_convert_encoding($tsv, $mojicode, $encode);
        }
        return $tsv;
    }

    /**
     * CSVダウンロード
     * @param string $csv CSV文字列もしくはインクルードするファイルのパス
     * @param string $file CSVファイルで保存する名前
     * @param string $include インクルードするファイルがある場合記入
     */
    public static function csvHeader(
        $csv,
        $file = 'data.csv',
        $include = false
    ) {
        header(
            sprintf('Content-disposition: attachment; filename=%s', $file)
        );
        header(
            sprintf('Content-type: application/octet-stream; name=%s', $file)
        );
        
        if ($include) {
            include($csv);
        } else {
            echo $csv;
        }
        session_write_close();
        exit(0);
    }
}
