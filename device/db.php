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
 * bindSelect  ステートメントの実行と抽出
 * stmtClose   ステートメントの解放
 * nameFlag    プレースホルダの種別
 * getId       IDの取得
 * lock        テーブル排他ロック
 * unlock      テーブル排他ロック解除
 * transaction トランザクションの開始
 * commit      トランザクションの確定
 * rollback    トランザクションの復帰
 * call        ルーチンの呼び出し
 *
 * @author   Hideshige Sawada
 * @version  1.4.0.0
 * @package  extension
 *
 */

namespace kiyomasa;

use PDO;
use PDOException;

class DbModule
{
    protected $connect; // データベースオブジェクト
    protected $stmt = []; // ステートメント
    protected $do = []; // ステートメントで実行中の動作メモを格納
    protected $name = []; // ステートメントで実行中のプレースホルダが名前の場合TRUE
    protected $column_count = []; // 更新するカラムの数
    public $debug; // デバッグフラグ
    public $disp_sql = ''; // 整形後の画面表示用SQL
    public $transaction_flag = false; // トランザクション実行中の場合TRUE
    public $lock_flag = false; // テーブル排他ロック中の場合TRUE
    protected $sql = ''; // 実行するSQL
    private $bind_params = []; // バインドする値
    private $time; // ステートメント開始時間
    public $qt_sum = 0; // 実行時間合計

    /**
     * 接続
     * @param string $db_server サーバーの名前
     * @param string $db_user ユーザーの名前
     * @param string $db_password ユーザーのパスワード
     * @param string $db_name データベースの名前
     * @param string $db_soft 使用するDBソフト
     * @return boolean 成否
     */
    public function connect(
        $db_server, 
        $db_user, 
        $db_password, 
        $db_name, 
        $db_soft = 'mysql'
    ) {
        try {
            $res = true;
            $dsn = sprintf(
                '%s:host=%s;dbname=%s',
                $db_soft,
                $db_server,
                $db_name
            );
            $this->connect = new PDO (
                $dsn, 
                $db_user, 
                $db_password, 
                array(
                    PDO::ATTR_PERSISTENT => false, 
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                )
            );
            $this->query(sprintf("SET NAMES '%s'", DEFAULT_CHARSET));
            $query = $this->query(
                "SET sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO'"
            );
            if (!$query) {
                throw new FwException('SQL MODE ERROR');
            }
        } catch (PDOException $e) {
            Log::error($e->getMessage());
            $res = false;
        } finally {
            return $res;
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
    public function insert(
        $table,
        $params,
        $replace = false,
        $statement_id = 'stmt'
    ) {
        $this->do[$statement_id] = 'insert';
        
        $res = false;

        if ($params) {
            if (AUTO_UPDATE_TIME and !isset($params['created_at'])) {
                $params['created_at'] = '';
            }
            if (AUTO_UPDATE_TIME and !isset($params['updated_at'])) {
                $this->column_count[$statement_id] = count($params);
                $params['updated_at'] = '';
            }
            $params_keys = array_keys($params);
            foreach ($params_keys as $v) {
                $params[$v] = ':' . $v;
            }
            $fields = implode(', ', $params_keys);
            $values = implode(', ', $params);

            $command = $replace ? 'REPLACE' : 'INSERT';
            $this->sql = sprintf(
                '%s INTO %s (%s) VALUES (%s)',
                $command,
                $table,
                $fields,
                $values
            );

            $res = $this->prepare($statement_id);
        }
        return $res;
    }

    /**
     * 抽出
     * @param string $table テーブル名
     * @param string $params 取り出す値
     * @param string $where 取り出す条件
     * @param string $statement_id プリペアドステートメントID
     * @return 成功した場合は結果をセットしたarrayまたはinteger、失敗した場合はfalse
     */
    public function select(
        $table,
        $params = '*',
        $where = '',
        $statement_id = 'stmt'
    ) {
        $this->do[$statement_id] = 'select';
        
        // プレースホルダが?か:nameかを判定
        $this->name[$statement_id] = preg_match('/\?/', $where) ? false : true;
        
        $this->sql = sprintf('SELECT %s FROM %s %s', $params, $table, $where);
        $res = $this->prepare($statement_id);
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
    public function update(
        $table,
        $params,
        $where = '',
        $statement_id = 'stmt'
    ) {
        $this->do[$statement_id] = 'update';
        
        // プレースホルダが?か:nameかを判定
        $this->name[$statement_id] = preg_match('/\?/', $where) ? false : true;

        if (is_array ($params)) {
            if (AUTO_UPDATE_TIME and !isset($params['updated_at'])) {
                $this->column_count[$statement_id] = count($params);
                $params['updated_at'] = '';
            }
            $values = [];
            $i = 0;

            $params_keys = array_keys($params);
            foreach ($params_keys as $key) {
                $var = $this->name[$statement_id] ? ':' . $key : '?';
                $values[$i] = sprintf('%s = %s', $key, $var);
                $i ++;
            }
            if (isset($values[1])) {
                $value = implode(', ', $values);
            } else {
                $value = $values[0];
            }

            $this->sql = sprintf('UPDATE %s SET %s %s', $table, $value, $where);

            $res = $this->prepare($statement_id);
        }
        return $res;
    }

    /**
     * 削除
     * @param string $table テーブル名
     * @param string $where 検索条件
     * @param string $statement_id プリペアドステートメントID
     * @return boolean 成功ならtrue、失敗ならfalse
     */
    public function delete(
        $table,
        $where = '',
        $statement_id = 'stmt'
    ) {
        $this->do[$statement_id] = 'delete';
        
        // プレースホルダが?か:nameかを判定
        $this->name[$statement_id] = preg_match('/\?/', $where) ? false : true;

        $this->sql = sprintf('DELETE FROM %s %s', $table, $where);

        $res = $this->prepare($statement_id);
        return $res;
    }


    /**
     * 実行
     * @param string $sql 実行するSQL文
     * @param string $dev_sql 画面表示用・ログ用SQL文(バイナリをテキストに置き換えたもの)
     * @param string $statement_id ステートメントID
     * @return object 実行結果(foreachすると配列になる)
     */
    public function query(
        $sql = null,
        $dev_sql = null,
        $statement_id = 'stmt'
    ) {
        try {
            global $g_counter;
            if ($sql) {
                $this->sql = $sql;
            }

            $this->before();
            $this->stmt[$statement_id] = $this->connect->query($this->sql);
            $qt = $this->after();

            // バイナリなど表示用・ログ用SQL文がある場合には書き換え
            if ($dev_sql) {
                $this->sql = $dev_sql;
            }

            if ($this->debug) {
                // 実行したSQL文と実行時間、変更行数
                $this->disp_sql .= sprintf(
                    "%d>%s; (%s) [%d]\n",
                    $g_counter,
                    $this->sql,
                    $qt,
                    $this->stmt[$statement_id]->rowCount()
                );
                $g_counter ++;
            }
            return $this->stmt[$statement_id];
        } catch (PDOException $e) {
            $this->dbLog($e->getMessage());
        }
    }


    /**
     * ステートメントの準備
     * @param string $statement_id プリペアドステートメントID
     * @param string $sql クエリ
     * @return boolean 成功した場合はステートメントのtrue、失敗した場合はfalse
     */
    public function prepare(
        $statement_id = 'stmt',
        $sql = null
    ) {
        try {
            global $g_counter;
            if ($sql) {
                $this->sql = $sql;
            }

            $this->stmt[$statement_id] = $this->connect->prepare($this->sql);

            if ($this->debug) {
                $this->disp_sql .= sprintf(
                    "%d>PREPARE %s FROM '%s';\n",
                    $g_counter,
                    $statement_id,
                    $this->sql
                );
                $g_counter ++;
            }

            return $this->stmt[$statement_id];
        } catch (PDOException $e) {
            $this->dbLog($e->getMessage());
        }
    }
    
    /**
     * 明示的にプレースホルダを変える
     * (通常は使用しなくても問題がないが必要な時があれば利用する)
     * @param boolean $name_flag プレースホルダが:nameならTRUE,?ならFALSE
     * @param string $statement_id プリペアドステートメントID
     */
    public function nameFlag(
        $name_flag,
        $statement_id = 'stmt'
    ) {
        $this->name[$statement_id] = $name_flag;
    }

    /**
     * ステートメントの実行
     * @param array $params 挿入する値
     * @param string $statement_id プリペアドステートメントID
     * @return integer 成功すれば変更した件数を返す
     */
    public function bind(
        $params = [],
        $statement_id = 'stmt'
    ) {
        try {
            global $g_counter;
            
            $this->before();

            if (!isset($this->name[$statement_id])) {
                $this->name[$statement_id] = true;
            }

            $i = 1;
            $u = $this->bind_params = [];
            if (is_array ($params)) {
                if (AUTO_UPDATE_TIME and !isset($params['created_at'])) {
                    if ($this->do[$statement_id] === 'insert') {
                        $params['created_at'] = TIMESTAMP;
                    }
                }
                if (AUTO_UPDATE_TIME and !isset($params['updated_at'])) {
                    if ($this->do[$statement_id] === 'insert' or $this->do[$statement_id] === 'update') {
                        array_splice(
                            $params,
                            $this->column_count[$statement_id],
                            0,
                            [TIMESTAMP]
                        );
                    }
                }

                foreach ($params as $k => $v) {
                    if ($k === 0) {
                        // array_spliceで入れたupdated_atはキーが0になるためキー名を変える
                        $k = 'updated_at';
                    }
                    $d_v = (strlen($v) > 5000) ? '[longtext or binary]' : $v;

                    if ($this->debug) {
                        if ($d_v === null) {
                            $this->disp_sql .= sprintf(
                                "%d>SET @%s = NULL;\n",
                                $g_counter,
                                $this->name[$statement_id] ? $k : $i
                            );
                        } else if (is_numeric($d_v)) {
                            $this->disp_sql .= sprintf(
                                "%d>SET @%s = %d;\n",
                                $g_counter,
                                $this->name[$statement_id] ? $k : $i,
                                $d_v
                            );
                        } else {
                            $this->disp_sql .= sprintf(
                                "%d>SET @%s = '%s';\n",
                                $g_counter,
                                $this->name[$statement_id] ? $k : $i,
                                $d_v
                            );
                        }
                        $g_counter ++;
                    }
                    $this->bind_params[] = $v;

                    $this->stmt[$statement_id]->bindValue(
                        $this->name[$statement_id] ? ':' . $k : $i,
                        $v,
                        is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR
                    );
                    $u[] = sprintf('@%s', $this->name[$statement_id] ? $k : $i);
                    $i ++;
                }
            }
            $this->stmt[$statement_id]->execute();
            $qt = $this->after($this->bind_params);
            $this->bind_params = [];

            if ($this->debug) {
                //デバッグ表示
                $using = count($u) ? sprintf('USING %s', implode(', ', $u)) : '';
                $this->disp_sql .= sprintf(
                    "%d>EXECUTE %s %s; (%s) [%d]\n",
                    $g_counter,
                    $statement_id,
                    $using,
                    $qt,
                    $this->stmt[$statement_id]->rowCount()
                );
                $g_counter ++;
            }
            return $this->stmt[$statement_id]->rowCount();
        } catch (PDOException $e) {
            $this->dbLog($e->getMessage());
        }
    }


    /**
     * ステートメントの結合と抽出
     * @param array,null $param 結合するパラメータ
     * @param string $statement_id プリペアドステートメントID
     * @param boolean $class_flag クラスを取得する場合TRUE
     * @return boolean
     */
    public function bindSelect(
        $param = null,
        $statement_id = 'stmt',
        $class_flag = false
    ) {
        try {
            $this->bind($param, $statement_id);
            $count = $this->stmt[$statement_id]->rowCount();
            $rows = false;
            if ($count and $class_flag) {
                $this->stmt[$statement_id]->setFetchMode(
                    PDO::FETCH_CLASS,
                    'stdClass'
                );
            } else if ($count) {
                $this->stmt[$statement_id]->setFetchMode(
                    PDO::FETCH_ASSOC
                );
                $rows = $this->stmt[$statement_id]->fetchAll();
            }
            return $rows;
        } catch (PDOException $e) {
            $this->dbLog($e->getMessage());
        }
    }


    /**
     * プリペアドステートメントの解放
     * @param string $statement_id プリペアドステートメントID
     */
    public function stmtClose($statement_id = 'stmt') {
        try {
            if (!$this->stmt[$statement_id]) {
                throw new FwException('No Statement');
            }
            
            $this->stmt[$statement_id]->closeCursor();
            
            if ($this->debug) {
                global $g_counter;
                $this->disp_sql .= sprintf(
                    "%d>DEALLOCATE PREPARE %s;\n",
                    $g_counter,
                    $statement_id
                );
                $g_counter ++;
            }
            
            unset($this->do[$statement_id]);
            unset($this->name[$statement_id]);
        } catch (PDOException $e) {
            $this->dbLog($e->getMessage());
        }
    }


    /**
     * AUTO_INCREMENTで最後に作成した番号を返す
     * @return integer
     */
    public function getId() {
        try {
            $res = $this->connect->lastInsertId();
            if (!$res) {
                throw new FwException('get id error');
            }
            return $res;
        } catch (PDOException $e) {
            $this->dbLog($e->getMessage());
        }
    }


    /**
     * テーブル排他ロック
     * （ロック中のテーブルは別の人は更新できない）
     * @params string $tables ロックするテーブル（カンマ区切り）
     * @return boolean
     */
    public function lock($tables) {
        // トランザクション使用中は実行できない。
        if ($this->transaction_flag) {
            throw new FwException('LOCK ERROR');
        }

        $this->lock_flag = true;
        $res = $this->query(
            sprintf(
                'LOCK TABLES %s WRITE',
                preg_replace('/,/', ' WRITE,', $tables)
            )
        );
        return $res;
    }


    /**
     * テーブル排他ロック解除
     * @return boolean
     */
    public function unlock() {
        $this->lock_flag = false;
        $res = $this->query('UNLOCK TABLES');
        return $res;
    }


    /**
     * トランザクションの開始
     * @return boolean
     */
    public function transaction() {
        try {
            $res = false;
            if ($this->debug and !$this->transaction_flag) {
                global $g_counter;
                $this->disp_sql .= $g_counter . ">START TRANSACTION;\n";
                $g_counter ++;
            }
            if (!$this->transaction_flag) {
                $this->transaction_flag = true;
                $res = $this->connect->beginTransaction();
            }
            return $res;
        } catch (PDOException $e) {
            $this->dbLog($e->getMessage());
        }
    }


    /**
     * トランザクションの確定
     * @return boolean
     */
    public function commit() {
        try {
            $res = false;
            if ($this->debug and $this->transaction_flag) {
                global $g_counter;
                $this->disp_sql .= $g_counter . ">COMMIT;\n";
                $g_counter ++;
            }
            if ($this->transaction_flag) {
                $this->transaction_flag = false;
                $res = $this->connect->commit();
            }
            return $res;
        } catch (PDOException $e) {
            $this->dbLog($e->getMessage());
        }
    }

    /**
     * トランザクションの復帰
     * @return boolean
     */
    public function rollback() {
        try {
            $res = false;
            if ($this->debug and $this->transaction_flag) {
                global $g_counter;
                $this->disp_sql .= $g_counter . ">ROLLBACK;\n";
                $g_counter ++;
            }
            if ($this->transaction_flag) {
                $this->transaction_flag = false;
                $res = $this->connect->rollBack();
            }
            return $res;
        } catch (PDOException $e) {
            $this->dbLog($e->getMessage());
        }
    }

    /**
     * ルーチンの呼び出し
     * @param string $name ルーチンの名前
     * @param mixed $param パラメータ
     * @param string $statement_id ステートメントID
     * @return boolean
     */
    public function call(
        $name,
        $param,
        $statement_id = 'stmt'
    ) {
        try {
            $params = implode(', ', $param);
            $res = $this->query(
                sprintf('CALL %s(%s)', $name, $params),
                null,
                $statement_id
            );
            return $res;
        } catch (PDOException $e) {
            $this->dbLog($e->getMessage());
        }
    }

    /**
     * 実行時間の測定開始
     */
    private function before()
    {
        $this->time = microtime(true);
    }

    /**
     * 実行時間の取得
     * @return float 実行時間
     */
    private function after()
    {
        $t = microtime(true);
        $qt = round($t - $this->time, 4);
        $this->qt_sum += $qt;
        if ($qt > 5) {
            Log::error(
                sprintf(
                    '[SLOW QUERY] %s [PARAM] %s (%s)',
                    $this->sql,
                    implode(',', $this->bind_params),
                    $qt
                )
            );
        }
        return $qt;
    }
    
    /**
     * エラーメッセージの成型
     * @param string $error
     */
    private function dbLog($error) {
        $error_mes = sprintf(
            "%s\n%s\n%s",
            $error,
            $this->sql,
            implode(',', $this->bind_params)
        );
        if ($this->debug) {
            //デバッグ表示
            dump($error_mes);
        }
        throw new FwException($error_mes);
    }
}