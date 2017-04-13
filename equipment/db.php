<?php
/**
 * データベースモジュール
 *
 * connect     接続
 * insert      作成
 * select      抽出
 * update      更新
 * delete      削除
 * query       実行
 * prepare     ステートメントの準備
 * bind        ステートメントの実行
 * bind_select ステートメントの実行と抽出
 * stmt_close  ステートメントの解放
 * get_id      IDの取得
 * lock        テーブル排他ロック
 * unlock      テーブル排他ロック解除
 * transaction トランザクションの開始
 * commit      トランザクションの確定
 * rollback    トランザクションの復帰
 * call        ルーチンの呼び出し
 *
 * @author   Hideshige Sawada
 * @version  1.3.6.0
 * @package  equipment
 *
 */

class db {
  private $_connect; //データベースオブジェクト
  private $_stmt = array (); //ステートメント
  private $_do = array (); //ステートメントで実行中の動作メモを格納
  private $_column_count = array (); //更新するカラムの数
  public $debug; //デバッグフラグ
  public $disp_sql = ''; //整形後の画面表示用SQL
  public $transaction_flag = false; //トランザクション実行中の場合TRUE
  public $lock_flag = false; //テーブル排他ロック中の場合TRUE
  private $_sql = ''; //実行するSQL
  private $_time; //ステートメント開始時間
  public $qt_sum = 0; //実行時間合計

  /**
   * 接続
   * @param string $db_server サーバーの名前
   * @param string $db_user ユーザーの名前
   * @param string $db_password ユーザーのパスワード
   * @param string $db_name データベースの名前
   * @param string $db_soft 使用するDBソフト
   * @return boolean 成否
   */
  public function connect( $db_server, $db_user, $db_password, $db_name, $db_soft = 'mysql' ) {
    try {
      $dsn = sprintf( '%s:host=%s;dbname=%s', $db_soft, $db_server, $db_name );
      $this->_connect = new PDO ( $dsn, $db_user, $db_password, array( PDO::ATTR_PERSISTENT => false, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION ) );
      $this->query( sprintf( "SET NAMES '%s'", DEFAULT_CHARSET ) );
      $this->query( "SET sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO'" );
      return true;
    } catch ( PDOException $e ) {
      log::error( $e->getMessage() );
      return false;
    }
  }


  /**
   * 作成
   * @param string $table テーブル名
   * @param array $params 挿入する値（配列からフィールド名とフィールド値を取り出す）
   * @param boolean $replace 同一キーの場合置換するかどうかの可否
   * @param string $statement_id プリペアドステートメントID
   * @return boolean
   */
  public function insert( $table, $params, $replace = false, $statement_id = 'stmt' ) {
    $this->_do[$statement_id] = 'insert';

    if ( $params ) {
      if ( AUTO_UPDATE_TIME and !isset ( $params['created_at'] ) ) {
        $params['created_at'] = '';
      }
      if ( AUTO_UPDATE_TIME and !isset ( $params['updated_at'] ) ) {
        $this->_column_count[$statement_id] = count( $params );
        $params['updated_at'] = '';
      }
      $params_keys = array_keys( $params );
      foreach ( $params_keys as $k => $v ) {
        $params_keys[$k] = sprintf( '%s', $v );
      }
      foreach ( $params as $k => $v ) {
        $params[$k] = '?';
      }
      $fields = implode( ', ', $params_keys );
      $values = implode( ', ', $params );

      $command = $replace ? 'REPLACE' : 'INSERT';
      $this->_sql = sprintf( '%s INTO %s ( %s ) VALUES ( %s )', $command, $table, $fields, $values );

      $res = $this->prepare( $statement_id );

      return $res;
    } else {
      return false;
    }
  }


  /**
   * 抽出
   * @param string $table テーブル名
   * @param string $params 取り出す値
   * @param string $where 取り出す条件
   * @param string $statement_id プリペアドステートメントID
   * @return 成功した場合は結果をセットしたarrayまたはinteger、失敗した場合はfalse
   */
  public function select( $table, $params = '*', $where = null, $statement_id = 'stmt' ) {
    $this->_do[$statement_id] = 'select';
    $this->_sql = sprintf( 'SELECT %s FROM %s %s', $params, $table, $where );
    $res = $this->prepare( $statement_id );
    return $res;
  }


  /**
   * 更新
   * @param string $table テーブル名
   * @param array $params 更新する値（配列からフィールド名とフィールド値を取り出す）
   * @param string $where 検索条件
   * @param string $statement_id プリペアドステートメントID
   * @return boolean 成功ならtrue、失敗ならfalse
   */
  public function update( $table, $params, $where, $statement_id = 'stmt' ) {
    $this->_do[$statement_id] = 'update';

    if ( is_array ( $params ) ) {
      if ( AUTO_UPDATE_TIME and !isset ( $params['updated_at'] ) ) {
        $this->_column_count[$statement_id] = count( $params );
        $params['updated_at'] = '';
      }
      $values = array ();
      $i = 0;
      if ( $this->debug ) $dev_values = array ();

      foreach ( $params as $k => $v ) {
        $var = '?';
        $values[$i] = sprintf( '%s = %s', $k, $var );
        if ( $this->debug ) {
          $dev_var = '?';
          $dev_values[$i] = sprintf( '%s = %s', $k, $dev_var );
        }
        $i ++;
      }
      if ( isset( $values[1] ) ) {
        $value = implode( ', ', $values );
        if ( $this->debug ) $dev_var = implode( ', ', $dev_values );
      } else {
        $value = $values[0];
        if ( $this->debug ) $dev_var = $dev_values[0];
      }

      $this->_sql = sprintf( 'UPDATE %s SET %s %s', $table, $value, $where );

      $res = $this->prepare( $statement_id );

      return $res;
    } else {
      return false;
    }
  }

  /**
   * 削除
   * @param string $table テーブル名
   * @param string $where 検索条件
   * @param string $statement_id プリペアドステートメントID
   * @return boolean 成功ならtrue、失敗ならfalse
   */
  public function delete( $table, $where, $statement_id = 'stmt' ) {
    $this->_do[$statement_id] = 'delete';
    
    $this->_sql = sprintf( 'DELETE FROM %s %s', $table, $where );

    $res = $this->prepare( $statement_id );
    return $res;
  }

  
  /**
   * 実行
   * @param string $sql 実行するSQL文
   * @param string $dev_sql 画面表示用・ログ用SQL文(バイナリをテキストに置き換えたもの)
   * @param string $statement_id ステートメントID
   * @return object 実行結果(foreachすると配列になる)
   */
  public function query( $sql = null, $dev_sql = null, $statement_id = 'stmt' ) {
    global $g_counter;
    if ( $sql ) $this->_sql = $sql;

    $this->_before();
    $this->_stmt[$statement_id] = $this->_connect->query( $this->_sql );
    $qt = $this->_after();

    if ( !$this->_stmt[$statement_id] ) {
      $e = $this->_connect->errorInfo();
      if ( $this->debug ) {
        $error_mes = sprintf( "%d %s\n%s", $e[0], $e[2], $this->_sql );
      } else {
        $error_mes = 'DBエラー';
      }
      throw new Exception( $error_mes );
    }

    //バイナリなど表示用・ログ用SQL文がある場合には書き換え
    if ( $dev_sql ) $this->_sql = $dev_sql;

    if ( $this->debug ) {
      //実行したSQL文と実行時間、変更行数
      $this->disp_sql .= sprintf(
        "%d■%s; (%s) [%d]\n"
        , $g_counter
        , $this->_sql
        , $qt
        , $this->_stmt[$statement_id]->rowCount()
      );
      $g_counter ++;
    }
    return $this->_stmt[$statement_id];
  }


  /**
   * ステートメントの準備
   * @param string $statement_id プリペアドステートメントID
   * @return 成功した場合はステートメントのtrue、失敗した場合はfalse
   */
  public function prepare( $statement_id = 'stmt', $sql = null ) {
    global $g_counter;
    if ( $sql ) $this->_sql = $sql;

    $this->_stmt[$statement_id] = $this->_connect->prepare( $this->_sql );

    if ( $this->debug ) {
      $this->disp_sql .= sprintf( "%d■PREPARE %s FROM '%s'; \n", $g_counter, $statement_id, $this->_sql );
      $g_counter ++;
    }
    return $this->_stmt[$statement_id];
  }


  /**
   * ステートメントの実行
   * @param array $params 挿入する値
   * @param string $statement_id プリペアドステートメントID
   * @return integer 成功すれば変更した件数を返す
   */
  public function bind( $params = array (), $statement_id = 'stmt' ) {
    global $g_counter;
    $this->_before();
    
    $i = 1;
    $u = $bind_params = array ();
    if ( is_array ( $params ) ) {
      if ( $this->debug ) {
        $this->disp_sql .= $g_counter . '■';
        $g_counter ++;
      }
      if ( AUTO_UPDATE_TIME and !isset ( $params['created_at'] ) ) {
        if ( $this->_do[$statement_id] == 'insert' ) {
          $params['created_at'] = TIMESTAMP;
        }
      }
      if ( AUTO_UPDATE_TIME and !isset ( $params['updated_at'] ) ) {
        if ( $this->_do[$statement_id] == 'insert' or $this->_do[$statement_id] == 'update' ) {
          array_splice( $params, $this->_column_count[$statement_id], 0, array( 'updated_at' => TIMESTAMP ) );
        }
      }
      foreach ( $params as $v ) {
        $d_v = ( strlen( $v ) > 5000 ) ? '[longtext or binary]' : $v;

        if ( $this->debug ) {
          if ( $d_v === null ) {
            $this->disp_sql .= sprintf( "SET @%d = NULL; ", $i );
          } else if ( is_numeric( $d_v ) ) {
            $this->disp_sql .= sprintf( "SET @%d = %d; ", $i, $d_v );
          } else {
            $this->disp_sql .= sprintf( "SET @%d = '%s'; ", $i, $d_v );
          }
        }
        $bind_params[] = $v;
        $this->_stmt[$statement_id]->bindValue( $i, $v, is_int( $v ) ? PDO::PARAM_INT : PDO::PARAM_STR );
        $u[] = sprintf( '@%d', $i );
        $i ++;
      }
      if ( $this->debug ) $this->disp_sql .= "\n";
    }

    $res = $this->_stmt[$statement_id]->execute();
    $qt = $this->_after( $bind_params );

    if ( $this->debug ) {
      $using = count( $u ) ? sprintf( 'USING %s', implode( ', ', $u ) ) : '';
      $this->disp_sql .= sprintf(
        "%d■EXECUTE %s %s; (%s) [%d]\n"
        , $g_counter
        , $statement_id
        , $using
        , $qt
        , $this->_stmt[$statement_id]->rowCount()
      );
      $g_counter ++;
    }

    if ( !$res ) {
      $e = $this->_stmt[$statement_id]->errorInfo();
      if ( $this->debug ) {
        $error_mes = sprintf(
          "%s %s\n%s\n%s"
          , $e[0], $e[2], $this->_sql, implode( ',', $bind_params )
        );
      } else {
        $error_mes = 'DBエラー';
      }
      throw new Exception( $error_mes );
    }

    return $this->_stmt[$statement_id]->rowCount();
  }


  /**
   * ステートメントの結合と抽出
   * @param array,null $param 結合するパラメータ
   * @param string $statement_id プリペアドステートメントID
   * @param boolean $object_flag オブジェクトとして取得する場合TRUE,配列として取得する場合FALSE
   * @return boolean
   */
  public function bind_select( $param = null, $statement_id = 'stmt', $object_flag = false ) {
    $this->bind( $param, $statement_id );
    $count = $this->_stmt[$statement_id]->rowCount();
    if ( !$count ) return false;

    if ( $object_flag ) {
      $this->_stmt[$statement_id]->setFetchMode( PDO::FETCH_CLASS, 'stdClass' );
    } else {
      $this->_stmt[$statement_id]->setFetchMode( PDO::FETCH_ASSOC );
    }
    $rows = $this->_stmt[$statement_id]->fetchAll();
    return $rows;
  }


  /**
   * プリペアドステートメントの解放
   * @param string $statement_id プリペアドステートメントID
   */
  public function stmt_close( $statement_id = 'stmt' ) {
    global $g_counter;
    if ( $this->_stmt[$statement_id] ) {
      $this->_stmt[$statement_id]->closeCursor();
      if ( $this->debug ) {
        $this->disp_sql .= sprintf( "%d■DEALLOCATE PREPARE %s;\n", $g_counter, $statement_id );
        $g_counter ++;
      }
    }
    unset ( $this->_do[$statement_id] );
  }


  /**
   * AUTO_INCREMENTで最後に作成した番号を返す
   * @return integer
   */
  public function get_id() {
    $res = $this->_connect->lastInsertId();
    if ( !$res ) throw new Exception( 'get id error' );
    return $res;
  }


  /**
   * テーブル排他ロック
   * （ロック中のテーブルは別の人は更新できない）
   * @params string $tables ロックするテーブル（カンマ区切り）
   * @return boolean
   */
  public function lock( $tables ) {
    //トランザクション使用中は実行できない。
    if ( $this->transaction_flag ) throw new Exception( 'LOCK ERROR' );

    $this->lock_flag = true;
    $tables = preg_replace( '/,/', ' WRITE,', $tables );
    $this->query( sprintf( 'LOCK TABLES %s WRITE', $tables ) );
    return $res;
  }


  /**
   * テーブル排他ロック解除
   * @return boolean
   */
  public function unlock() {
    $this->lock_flag = false;
    $res = $this->query( 'UNLOCK TABLES' );
    return $res;
  }


  /**
   * トランザクションの開始
   * @return boolean
   */
  public function transaction() {
    global $g_counter;
    if ( !$this->transaction_flag ) {
      $this->transaction_flag = true;
      $res = $this->_connect->beginTransaction();
      if ( $this->debug ) {
        $this->disp_sql .= $g_counter . "■START TRANSACTION;\n";
        $g_counter ++;
      }
      return $res;
    } else {
      return false;
    }
  }


  /**
   * トランザクションの確定
   * @return boolean
   */
  public function commit() {
    global $g_counter;
    if ( $this->transaction_flag ) {
      $this->transaction_flag = false;
      $res = $this->_connect->commit();
      if ( $this->debug ) {
        $this->disp_sql .= $g_counter . "■COMMIT;\n";
        $g_counter ++;
      }
      return $res;
    } else {
      return false;
    }
  }

  /**
   * トランザクションの復帰
   * @return boolean
   */
  public function rollback() {
    global $g_counter;
    if ( $this->transaction_flag ) {
      $this->transaction_flag = false;
      $res = $this->_connect->rollBack();
      if ( $this->debug ) {
        $this->disp_sql .= $g_counter . "■ROLLBACK;\n";
        $g_counter ++;
      }
      return $res;
    } else {
      return false;
    }
  }

  /**
   * ルーチンの呼び出し
   * @param string $name ルーチンの名前
   * @param mixed $param パラメータ
   * @param string $statement_id ステートメントID
   * @return boolean
   */
  public function call( $name, $param, $statement_id = 'stmt' ) {
    $params = implode( ', ', $param );
    $res = $this->query( sprintf( "CALL %s(%s)", $name, $params ), null, $statement_id );
    return $res;
  }
  
  /**
   * 実行時間の測定開始
   */
  private function _before() {
    $this->_time = microtime( true );
  }

  /**
   * 実行時間の取得
   * @param array $param プリペアドステートメントに渡す値の配列
   * @return float 実行時間
   */
  private function _after( $param = array() ) {
    $t = microtime( true );
    $qt = round( $t - $this->_time, 4 );
    $this->qt_sum += $qt;
    if ( $qt > 5 ) {
      log::error(
        sprintf(
          '[SLOW QUERY] %s [PARAM] %s (%s)'
          , $this->_sql
          , implode( $param, ',' )
          , $qt
        )
      );
    }
    return $qt;
  }
}

