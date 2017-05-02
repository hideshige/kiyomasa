<?php
/**
 * CSV モジュール
 *
 * @author   Hideshige Sawada
 * @version  1.2.0.0
 * @package  equipment
 */

class csv {

  /**
   * CSVファイルから配列として取得
   * @param string $file CSVのファイルパス
   * @param string $encode エンコード
   * @param string $mojicode CSVファイルの文字コード
   * @return array 取得した配列
   */
  public static function csv_file_to_array($file, $encode = DEFAULT_CHARSET, $mojicode = 'SJIS-win') {
    setlocale(LC_ALL, 'ja_JP');
    $data = array();
    if (file_exists($file)) {
      //ファイルの取得
      $contents = file_get_contents($file);
      if ($encode != $mojicode) {
        //文字コードの変換
        $contents = mb_convert_encoding($contents, $encode, $mojicode);
      }
      //ダブルクォートを一旦退避させて無害化
      $contents = preg_replace('/"/', ':::QU:::', $contents);
      $contents = htmlspecialchars($contents, ENT_QUOTES);
      $contents = preg_replace('/:::QU:::/', '"', $contents);
      //一時ファイルに保存する
      $f = tmpfile();
      fwrite($f, $contents);
      rewind($f);
      $i = 0;
      while ($array = self::_fgetcsv_reg($f)) {
        $num = count($array);
        for ($c = 0; $c < $num; $c ++) {
          $data[$i][$c] = $array[$c];
        }
      $i ++;
      }
      fclose($f);
    }
    return $data;
  }

  /**
   * fgetcsv()の文字化けの問題を解消
   * @param object $handle ファイルポインタ
   * @return false or array 結果
   */
  private static function _fgetcsv_reg(&$handle) {
    $d = ',';
    $e = '"';
    $line = '';
    $dummy = array();
    $csv_matches = array();
    $eof = false;
    $itemcnt = 0;
    while (!$eof) {
      $this_line = fgets($handle);
      $itemcnt += preg_match_all('/'.$e.'/', preg_replace('/'.$e.$d.'(.*?)'.$e.'/', '', $this_line), $dummy);
      if ($itemcnt % 2 == 0) {
        $eof = true;
      }
      $line .= $this_line;
    }
    $csv_line = preg_replace('/(?:\\r\\n|[\\r\\n])?$/', $d, trim($line));
    $csv_pattern = '/('.$e.'[^'.$e.']*(?:'.$e.$e.'[^'.$e.']*)*'.$e.'|[^'.$d.']*)'.$d.'/';
    preg_match_all($csv_pattern, $csv_line, $csv_matches);
    $csv_data = $csv_matches[1];
    for ($csv_i = 0; $csv_i < count($csv_data); $csv_i ++) {
      $csv_data[$csv_i] = preg_replace('/^'.$e.'(.*)'.$e.'$/s', '$1', $csv_data[$csv_i]);
      $csv_data[$csv_i] = preg_replace('/'.$e.$e.'/', $e, $csv_data[$csv_i]);
    }
    $res = empty($line) ? false : $csv_data;
    return $res;
  }
  
  /**
   * 配列をCSVに変換
   * @param array $get_data 配列データ
   * @param boolean $header 1行目を書き出すか否か
   * @param string $mojicode CSVファイルの文字コード
   * @param string $encode エンコード
   * @return string CSVデータ
   */
  public static function array_to_csv($get_data, $header = true, $mojicode = 'SJIS-win', $encode = DEFAULT_CHARSET) {
    if (!$get_data) { return null; }
    if (sizeof($get_data) > CSV_MAX) { return null; }
    
    $csv_arr = array ();
    
    foreach ($get_data as $k => $v) {
      if (!$k and $header) {
        $tmp = array_keys($v);
        $csv_arr[] = '"'.implode('","', $tmp).'"';
      }
      $csv_arr[] = '"'.implode('","', $v).'"';
    }
    $csv = implode("\n", $csv_arr);
    $csv = str_replace('&quot;', '""', $csv);//区切り文字がずれないように"をエスケープする
    $csv = citadel::h_decode($csv);
    if ($mojicode != $encode) {
      $csv = mb_convert_encoding($csv, $mojicode, $encode);
    }
    return $csv;
  }

  /**
   * 配列をTSVに変換
   * @param array $get_data 配列データ
   * @param boolean $header 1行目を書き出すか否か
   * @param string $mojicode CSVファイルの文字コード
   * @param string $encode エンコード
   * @return string TSVデータ
   */
  public static function array_to_tsv($get_data, $header = true, $mojicode = DEFAULT_CHARSET, $encode = DEFAULT_CHARSET) {
    if (!$get_data) { return null; }
    if (sizeof($get_data) > CSV_MAX) { return null; }
    
    $tsv_arr = array ();
    
    foreach ($get_data as $k => $v) {
      if (!$k and $header) {
        $tmp = array_keys($v);
        $tsv_arr[] = implode("\t", $tmp) ;
      }
      $tsv_arr[] = implode("\t", $v);
    }
    $tsv = implode("\n", $tsv_arr);
    $tsv = citadel::h_decode($tsv);
    if ($mojicode != $encode) {
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
  public static function csv_header($csv, $file = 'data.csv', $include = false) {
    header(sprintf('Content-disposition: attachment; filename=%s', $file));
    header(sprintf('Content-type: application/octet-stream; name=%s', $file));
    if ($include) {
      include ($csv);
    } else {
      echo $csv;
    }
    session_write_close();
    exit;
  }

  /**
   * シェルによりファイルに保存
   * @global array $srv Webサーバー
   * @param object $db データベースオブジェクト
   * @param string $table データベースのテーブル
   * @param string $where データベースの検索条件
   * @param string $filename CSVのファイル名
   * @param string $select 抽出する行
   * @param boolean $header 1行目を書き出すか否か
   * @param boolean $num_flag CSVに行番号を入れる場合TRUE
   */
  public static function make_csv($table, $where, $filename, $select = '*', $header = true, $num_flag = false) {
//    global $srv;

    S::$dbs->select($table, 'COUNT(*) AS count', $where);
    $res = S::$dbs->bind_select();
    $count = $res[0]['count'];
    $do_count = ceil($count / 1000);

    $f = fopen(sprintf('../logs/%s', $filename), "w");
    $num = 1;

    if ($count) {
      $first_flag = $header;
      for ($i = 0; $i < $do_count; $i ++) {
        $limit_start = $i * 1000;
        $limit = sprintf('%s LIMIT %d, 1000', $where, $limit_start);
        S::$dbs->select($table, $select, $limit);
        $res = S::$dbs->bind_select();
        $data = array ();
        if ($num_flag and $res) {
          foreach ($res as $num_k => $num_v) {
            $data[$num_k]['num'] = $num;
            foreach ($res[$num_k] as $num_k_k => $num_v_v) {
              $data[$num_k][$num_k_k] = $num_v_v;
            }
            $num ++;
          }
        } else {
          $data = $res;
        }
        $csv = self::array_to_csv($data, $first_flag);
        fwrite($f, $csv);
        $first_flag = false;
      }
    }
    fclose($f);

//    foreach ($srv as $v) {
//      system(
//        sprintf(
//          "sudo scp -p ../logs/%s root\@%s:%slogs/%s"
//          , $filename, $v, SERVER_PATH, $filename
//       )
//     );
//    }
  }
}
