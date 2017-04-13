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
 * <!-- ELEMENT *** -->にはelementフォルダの部分テンプレートが挿入される。
 * 
 * @author   Hideshige Sawada
 * @version  1.0.10.0
 * @package  view
 * 
 */
class view {

  /**
   * テンプレートファイルを読み込みデータを埋め込んで表示させる
   * @param string $tpl ファイル名
   * @param array $disp テンプレートに埋め込むデータ配列
   * @param string $folder テンプレートのあるフォルダ
   */
  public static function template( $tpl, $disp, $folder = '' ) {
    $content = self::_open( $tpl, false, $folder );
    if ( !$content ) return false;

    //エレメントの反映
    $content = self::_element_match( $content );
    $content = str_replace( "\n", '=br=', $content );

    //コンテンツの下準備のため先に変換しておく
    if ( isset ( $disp['REPLACE'] ) ) {
      foreach ( $disp['REPLACE'] as $k => $v ) {
        $content = str_replace( '{' . $k . '}', $v, $content );
      }
      unset( $disp['REPLACE'] );
    }

    $content = self::_match( $disp, $content );
    $content = str_replace( '=br=', "\n", $content );
    return $content;
  }

  private static function _element_match( $content ) {
    //部分テンプレートの挿入
    preg_match_all( '/<!-- ELEMENT (.*?) -->/', $content, $element_match );

    if ( $element_match[1] ) {
      $element_match[1] = array_unique( $element_match[1] );
      foreach ( $element_match[1] as $ek => $element_name ) {
        $element = self::_open( $element_name, true );
        $element = str_replace( "\n", '=br=', $element );
        $content = str_replace( $element_match[0][$ek], $element, $content );
      }
      $content = self::_element_match( $content );
    }
    return $content;
  }


  /**
   * テンプレートにパラメーターを挿入
   * @param array $disp テンプレートに埋め込むデータ配列
   * @param string $content テンプレート
   * @return string 挿入し終えたコンテンツ
   */
  private static function _match( $disp, $content ) {
    preg_match_all( '/<!-- BEGIN (.*?) -->/', $content, $match );

    foreach ( $match[1] as $name ) {
      $pattern = '/<!-- BEGIN ' . $name . ' -->(.*)<!-- END ' . $name . ' -->/';
      preg_match_all( $pattern, $content, $match1 );
      $tag_data = $original = isset ( $match1[1][0] ) ? $match1[1][0] : null;
      $all_tag = '';

      preg_match_all( '/{(.*?)}/', $original, $match2 );

      if ( isset ( $disp[$name] ) ) {
        $num = count( $disp[$name] );
        for ( $i = 0; $i < $num; $i ++ ) {
          $tag_data = self::_match( @$disp[$name][$i], $tag_data );
          foreach ( $match2[1] as $data ) {
            if ( isset ( $disp[$name][$i][$data] ) ) {
              $change_data = $disp[$name][$i][$data];
            } else if ( preg_match( '/[ ,:;=]/', $data ) ) {
              //改行やスペースが入っているJSデータなどの{}は変更しない
              $change_data = '{' . $data . '}';
            } else {
              $change_data = '';
            }
            $tag_data = str_replace( '{' . $data . '}', $change_data, $tag_data );
          }
          $all_tag .= $tag_data;
          $tag_data = $original;
        }
        $original = '<!-- BEGIN ' . $name . ' -->' . $original . '<!-- END ' . $name . ' -->';
        $content = str_replace( $original, $all_tag, $content );
        $content = str_replace( '<!-- BEGIN ' . $name . ' -->', '', $content );
        $content = str_replace( '<!-- END ' . $name . ' -->', '', $content );
      } else if ( isset ( $match1[0][0] ) ) {
        $content = str_replace( $match1[0][0], '', $content );
      }
    }
    return $content;
  }


  /**
   * テンプレートの読み込み
   * @param string $tpl テンプレートファイル名
   * @param boolean $elm 部分テンプレートか否か
   * @param string $folder テンプレートのあるフォルダ
   * @return string or null 読み込んだコンテンツ
   */
  private static function _open( $tpl, $elm, $folder = '' ) {
    $add = preg_match( '<\.>', $tpl ) ? '' : '.html';
    $element = $elm ? 'element/' : '';
    $tpl_folder = ( MOBILE_FLAG and !isset ( $_SESSION['mobile_pc_flag'] ) ) ? 'tpl_mobile/' : 'tpl/';
    $fname = SERVER_PATH . $tpl_folder . $folder . $element . $tpl . $add;
    if ( $tpl_folder == 'tpl_mobile/' and !file_exists( $fname ) ) {
      $fname = SERVER_PATH . 'tpl/' . $folder . $element . $tpl . $add;
    }
    $fh = @fopen( $fname, 'r' );
    if ( !$fh ) return null;
    $content = fread( $fh, max( 1, filesize( $fname ) ) );
    fclose( $fh );
    return $content;
  }
}
