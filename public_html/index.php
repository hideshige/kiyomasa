<?php
/**
 * モデルを実行しビューに表示させるコントローラクラス
 *
 * @author   Hideshige Sawada
 * @version  1.3.0.0
 * @package  controller
 */

header("P3P: CP='UNI CUR OUR'");//コンパクトプライバシーポリシー
header('X-XSS-Protection: 1; mode=block');//XSS対策
header('Content-Type: text/html;charset=UTF-8');
$first_time = microtime(true);
$first_memory = memory_get_usage() / 1024;
require_once('../common/env.php');
require_once('../common/define.php');
require_once('../equipment/log.php');
require_once('../equipment/view.php');
require_once('../common/citadel.php');
require_once('../equipment/db.php');
require_once('../equipment/memcached.php');
require_once('../equipment/session.php');
new castle();

class castle {
  private $_mainte;//メンテナンスモード
  private $_debug;//デバッグモード
  private $_error_flag = false;//初回エラーかどうか（循環防止のため）

  /*
   * オブジェクトの作成
   */
  public function __construct() {
    try {
      S::$jflag = false;
      S::$dbm = new db();
      $res_dbm = S::$dbm->connect(DB_MASTER_SERVER, DB_MASTER_USER, DB_MASTER_PASSWORD, DB_MASTER_NAME);
      if (!$res_dbm) { throw new Exception('DB_MASTER Connect Error'); }
      S::$dbs = new db();
      $res_dbs = S::$dbs->connect(DB_SLAVE_SERVER, DB_SLAVE_USER, DB_SLAVE_PASSWORD, DB_SLAVE_NAME);
      if (!$res_dbs) {
        log::error('DB_SLAVE Connect Error ---> DB_MASTER Connect Change');
        $res_dbs2 = S::$dbs->connect(DB_MASTER_SERVER, DB_MASTER_USER, DB_MASTER_PASSWORD, DB_MASTER_NAME);
        if (!$res_dbs2) { throw new Exception('DB_MASTER Connect Error'); }
      }
      
      S::$mem = new memcached_module();
      
      $this->_debug = false;
//      global $g_ip_address;
//      foreach ($g_ip_address as $k => $v) {
//        $res_ip = preg_match(sprintf('/%s$/', $v), IP_ADDRESS);
//        if ($res_ip and ENV <= 2) $this->_debug = true;
//      }
      if (ENV == 0) { $this->_debug = true; }
      S::$dbm->debug = $this->_debug;
      S::$dbs->debug = $this->_debug;
      S::$mem->debug = $this->_debug;

      S::$post = $this->_h($_POST);
      S::$get = $this->_h($_GET);
      if (isset(S::$get['url'])) {
        S::$url = explode('/', preg_replace('<^/>', '', S::$get['url']));
        unset(S::$get['url']);
      }

      //セッションのセット
      new session();

      //URLの指定がなければトップページを指定
      $folder = '';
      $url_num = 0;
      global $g_folder;
      if ($g_folder) {
        foreach ($g_folder as $v) {
          if (S::$url[0] == $v) {
            $folder = $v . '/';
            $url_num = 1;
            break;
          }
        }
      }
      $pagename = (isset(S::$url[$url_num]) and S::$url[$url_num]) ? S::$url[$url_num] : 'index';

      //メンテナンスモードの判定
      $this->_mainte = 0;

      if ($this->_mainte and !$this->_debug) {
        $pagename = 'mainte';
        $folder = '';
      }

      $this->_disp($pagename, $folder);
    } catch (Exception $e) {
      $error = sprintf('%s(%s) %s', str_replace(SERVER_PATH, '', $e->getFile()), $e->getLine(), $e->getMessage());
      log::error($error);
      //テスト環境の場合、デバッグ用のエラーを表示する
      if ($this->_debug) {
        echo $error;
      } else {
        echo 'エラーになりました。';
      }
      exit;
    } finally {
    }
  }


  /*
   * モジュールの組み込み
   * $model モデルのオブジェクト（参照渡し）
   * return bool
   */
  private function _get_equipment(&$model) {
    if (count($model->equipment)) {
      $mods = array();
      foreach ($model->equipment as $v) {
        if (!include_once(sprintf('../equipment/%s.php', $v))) { return false; }
        $mods[$v] = new $v;
      }
      $model->obj['mods'] = $mods;
    }
    return true;
  }


  /**
   * モデルを実行し、ビューにデータを渡す
   * @param string $pagename 実行するモデルの名前
   * @param string $folder モデルのフォルダ名
   */
  private function _disp($pagename, $folder = '') {
    try {
      $file = sprintf('../models/%s%s.php', $folder, $pagename);
      if (!file_exists($file) or !include_once($file)) {
        header('HTTP/1.0 404 Not Found');
        throw new Exception(sprintf('%s read notice', $pagename));
      }

      $model = new $pagename;
      $res_equ = $this->_get_equipment($model);
      if (!$res_equ) {
        throw new Exception($pagename . ' equipment read notice');
      }
      $res = $model->logic();
      if ($res === false) {
        throw new Exception($pagename . ' logic notice');
      }

      session_write_close();
        
      if (!S::$jflag) {
        $tpl = array ();
        for ($i = 0; $i < count($model->tpl); $i ++) {
          if (isset(S::$disp[$i])) {
            $tpl[$model->tpl[$i]] = S::$disp[$i];
          } else {
            $tpl[$model->tpl[$i]] = array ();
          }
        }

        if ($tpl) {
          foreach ($tpl as $k => $v) {
            echo view::template($k, $v, $folder);
          }
        }

        $this->_disp_debug();
      } else {
        if (is_array ($res)) {
          $json = $res;
          if ($this->_debug) {
            global $dump;
            $json['debug'] = "【DB SLAVE】\n" . S::$dbs->disp_sql;
            $json['debug'] .= "----------------------------------------------------------------------\n";
            $json['debug'] .= "【DB MASTER】\n" . S::$dbm->disp_sql;
            $json['debug'] .= "----------------------------------------------------------------------\n";
            $json['debug'] .= "【MEMCACHED】\n" . S::$mem->disp_mem;
            $json['debug'] .= "----------------------------------------------------------------------\n";
            $json['debug'] .= "【DUMP】\n" . $dump;
            $json['debug'] .= "----------------------------------------------------------------------\n";
            $json['debug'] .= "【MEMORY】\n" . number_format(memory_get_peak_usage() / 1024) . 'KB';
          }
          echo json_encode($json);
          exit;
        }
      }
    } catch (Exception $e) {
      if (S::$dbm->transaction_flag) {
        //トランザクションを実行中に例外処理が起きた場合、ロールバックする
        S::$dbm->rollback();
      } else if (S::$dbm->lock_flag) {
        //テーブル排他ロック中に例外処理が起きた場合、テーブル排他ロックを解除する
        S::$dbm->unlock();
      }

      if (ENV == 0 or !preg_match('/notice/', $e->getMessage())) {
        $error = sprintf('%s(%s) %s', str_replace(SERVER_PATH, '', $e->getFile()), $e->getLine(), $e->getMessage());
        log::error($error);
      }

      //エラーページの表示
      //テスト環境の場合、デバッグ用のエラーを表示する
      if (!S::$jflag) {
        if ($this->_debug and isset($error)) {
          S::$disp[1]['MESSAGE'][0]['message'] = $error;
        }
        if (!$this->_error_flag) {
          $this->_error_flag = true;//循環防止のフラグ
          $this->_disp('error_page', $folder);//エラー画面モデルの読み込み
        } else {
          echo '申し訳ございません。しばらく経ってからアクセスしてください。';
          if ($this->_debug) {
            echo '（同階層のerror_page.phpに問題があった場合、この画面が出ます。）';
          }
        }
      } else {
        $array = array ();
        $array['alert'] = $this->_debug ? $error : 'エラー';
        echo json_encode($array);
      }
   
      //DBセッションを明示的にリセット
      session_write_close();
      S::$dbm = null;
      S::$dbs = null;
      exit;
    } finally {
    }
  }
  
  /**
   * デバッグ情報の表示
   */
  private function _disp_debug() {
    if ($this->_debug) {
      ob_start();
      if (S::$post) {
        var_dump(S::$post);
      }
      $post = ob_get_clean();
      ob_start();
      if (S::$get) {
        var_dump(S::$get);
      }
      $get = ob_get_clean();
      ob_start();
      if (S::$url) {
        var_dump(S::$url);
      }
      $url = ob_get_clean();
      ob_start();
      if ($_FILES) {
        var_dump($_FILES);
      }
      $files = ob_get_clean();

      global $first_memory;
      global $first_time;
      global $dump;
      echo sprintf(
        '<p style="background:#ffcc00;clear:both;">【DB SLAVE】<br />%s</p>'
        , nl2br(htmlspecialchars(S::$dbs->disp_sql))
      );
      echo sprintf(
        '<p style="background:#ff8800;">【DB MASTER】<br />%s</p>'
        , nl2br(htmlspecialchars(S::$dbm->disp_sql))
      );
      echo sprintf(
        '<p style="background:#99aaff;">【MEMCACHED】<br />%s</p>'
        , nl2br(htmlspecialchars(S::$mem->disp_mem))
      );
      echo sprintf(
        '<pre><p style="background:#ffcc66;">【POST】<br />%s</p></pre>'
        , htmlspecialchars($post)
      );
      echo sprintf(
        '<pre><p style="background:#ffcc33;">【GET】<br />%s</p></pre>'
        , htmlspecialchars($get)
      );
      echo sprintf(
        '<pre><p style="background:#ffdd99;">【URL】<br />%s</p></pre>'
        , htmlspecialchars($url)
      );
      echo sprintf(
        '<pre><p style="background:#eecc00;">【FILES】<br />%s</p></pre>'
        , htmlspecialchars($files)
      );
      echo sprintf(
        '<pre><p style="background:#ffff00;">【DUMP】<br />%s</p></pre>'
        , htmlspecialchars($dump)
      );

      $peak_memory = memory_get_peak_usage() / 1024;
      $last_time = microtime(true);
      echo sprintf(
        '<p style="background:#ff0000;">メンテナンスモード<br />
          OS: %s PHP ver: %s<br />
          メモリ使用量: %s KB (固定分) + %s KB (追加分) = %s KB<br />
          実行時間: %s 秒<br />
          IP: %s<br />
          タイムスタンプ: %s (%d)</p>',
        PHP_OS, phpversion(),
        number_format($first_memory),
        number_format($peak_memory - $first_memory),
        number_format($peak_memory),
        round($last_time - $first_time, 3),
        IP_ADDRESS,
        TIMESTAMP, time()
      );
    }
  }  

  /*
   * サニタイズ
   */
  private function _h($data) {
    if (is_array($data)) {
      foreach($data as $k => $v) {
        $data[$k] = $this->_h($v);
      }
    } else {
      $data = preg_replace('/[\x00-\x09\x0B\x0C\x0E-\x1F\x7F]/', '', $data);//改行コード以外のコントロールコードを排除
      //UNICODE不可視文字トリム
      $invisible_utf8_codes = array(
        '&#x00AD;',
        '&#x2000;','&#x2001;','&#x2002;','&#x2003;','&#x2004;','&#x2005;','&#x2006;','&#x2007;','&#x2008;','&#x2009;','&#x200A;','&#x200B;','&#x200C;','&#x200D;','&#x200E;','&#x200F;',
        '&#x2028;','&#x2029;','&#x202A;','&#x202B;','&#x202C;','&#x202D;','&#x202E;','&#x202F;',
        '&#x205F;',
        '&#x2060;','&#x2061;','&#x2062;','&#x2063;','&#x2064;','&#x2065;','&#x2066;','&#x2067;','&#x2068;','&#x2069;','&#x206A;','&#x206B;','&#x206C;','&#x206D;','&#x206E;','&#x206F;',
        '&#x2322;','&#x2323;',
        '&#x2800;',
        '&#x3164;',
        '&#xA717;','&#xA718;','&#xA719;','&#xA71A;',
        '&#xA720;','&#xA721;',
        '&#xFE00;','&#xFE01;','&#xFE02;','&#xFE03;','&#xFE04;','&#xFE05;','&#xFE06;','&#xFE07;','&#xFE08;','&#xFE09;','&#xFE0A;','&#xFE0B;','&#xFE0C;','&#xFE0D;','&#xFE0E;','&#xFE0F;',
        '&#xFEFF;',
        '&#xFFF0;','&#xFFF1;','&#xFFF2;','&#xFFF3;','&#xFFF4;','&#xFFF5;','&#xFFF6;','&#xFFF7;','&#xFFF8;',
      );
      $invisible_strs = array_map(
        function ($code) {
          return html_entity_decode($code, ENT_NOQUOTES, 'UTF-8');
        },
        $invisible_utf8_codes
      );
      $data = str_replace($invisible_strs, '', $data);
      $data = htmlspecialchars($data, ENT_QUOTES);
      global $g_change_chara;
      foreach ($g_change_chara as $ck => $cv) {
        $data = str_replace($cv, $ck, $data);
      }
      //if (MOBILE_FLAG) {
      //  $data = mb_convert_encoding($data, DEFAULT_CHARSET, 'Shift_JIS');
      //}
    }
    return $data;
  }
}

/**
 * パラメータのショートカット用スタティックオブジェクト
 *
 */
class S {
  static $post;//整形後のPOSTパラメータ
  static $get;//整形後のGETパラメータ
  static $url;//URLパラメータ
  static $dbm;//DBマスターモジュール
  static $dbs;//DBスレーブモジュール
  static $mem;//memcachedモジュール
  static $disp;//テンプレートデータ
  static $user;//セッション上のユーザーデータ
  static $ouser;//ページに表示するユーザーデータ
  static $jflag;//そのモデルがJSON形式かHTML形式か
}

/**
 * ダンプをバッファに保存
 * @global string $dump ダンプ用バッファ
 * @param mixed ダンプするデータをカンマ区切りで記入する
 */
$dump = '';
function dump() {
  global $dump;
  $bt = debug_backtrace();
  $dump .= sprintf("%s %s\n", $bt[0]['file'], $bt[0]['line']);
  ob_start();
  foreach ($bt[0]['args'] as $v) {
    var_dump($v);
  }
  $dump .= ob_get_clean();
  return $dump;
}
