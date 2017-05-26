<?php
/**
 * データベース
 *
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
 * @author   Sawada Hideshige
 * @version  1.4.4.1
 * @package  device
 *
 */

namespace Php\Framework\Device;

class Db extends DbModule
{
    /**
     * 作成
     * @param string $table テーブル名
     * @param array $params 挿入する値（配列からフィールド名とフィールド値を取り出す）
     * @param bool $replace 同一キーの場合置換するかどうかの可否
     * @param string $statement_id プリペアドステートメントID
     * @return object|bool
     */
    public function insert(
        string $table,
        array $params,
        bool $replace = false,
        string $statement_id = 'stmt'
    ) {
        $this->do[$statement_id] = 'insert';
        $this->name[$statement_id] = true;

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
     * @return object|bool
     */
    public function select(
        string $table,
        string $params = '*',
        string $where = '',
        string $statement_id = 'stmt'
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
     * @return object|bool
     */
    public function update(
        string $table,
        array $params,
        string $where = '',
        string $statement_id = 'stmt'
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
     * @return object|bool
     */
    public function delete(
        string $table,
        string $where = '',
        string $statement_id = 'stmt'
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
     * @global int $g_counter
     * @param string $sql 実行するSQL文
     * @param string $dev_sql 画面表示用・ログ用SQL文(バイナリをテキストに置き換えたもの)
     * @param string $statement_id ステートメントID
     * @return object|bool
     */
    public function query(
        string $sql = null,
        string $dev_sql = null,
        string $statement_id = 'stmt'
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
                    "{{COUNTER %d}}%s; {{TIME}} (%s秒) [行数 %d]\n",
                    $g_counter,
                    $this->sql,
                    $qt,
                    $this->stmt[$statement_id]->rowCount()
                );
                $g_counter ++;
            }
            return $this->stmt[$statement_id];
        } catch (\PDOException $e) {
            $this->dbLog($e->getMessage());
        }
    }


    /**
     * ステートメントの準備
     * @global int $g_counter
     * @param string $statement_id プリペアドステートメントID
     * @param string $sql クエリ
     * @return object|bool
     */
    public function prepare(
        string $statement_id = 'stmt',
        string $sql = ''
    ) {
        try {
            global $g_counter;
            if ($sql) {
                $this->sql = $sql;
            }

            $this->stmt[$statement_id] = $this->connect->prepare($this->sql);

            if ($this->debug) {
                $this->disp_sql .= sprintf(
                    "{{COUNTER %d}}PREPARE {{STATEMENT}}%s FROM '%s';\n",
                    $g_counter,
                    $statement_id,
                    $this->sql
                );
                $g_counter ++;
            }

            return $this->stmt[$statement_id];
        } catch (\PDOException $e) {
            $this->dbLog($e->getMessage());
        }
    }
    
    /**
     * 明示的にプレースホルダを変える
     * (通常は使用しなくても問題がないが必要な時があれば利用する)
     * @param bool $name_flag プレースホルダが:nameならTRUE,?ならFALSE
     * @param string $statement_id プリペアドステートメントID
     */
    public function nameFlag(
        bool $name_flag,
        string $statement_id = 'stmt'
    ): void {
        $this->name[$statement_id] = $name_flag;
    }

    /**
     * ステートメントの実行
     * @global int $g_counter
     * @param array $params 挿入する値
     * @param string $statement_id プリペアドステートメントID
     * @return int 成功すれば変更した件数を返す
     */
    public function bind(
        array $params = [],
        string $statement_id = 'stmt'
    ): int {
        try {
            global $g_counter;
            
            $this->before();
            
            if (!isset($this->name[$statement_id])) {
                $this->name[$statement_id] = true;
            }

            $i = 1;
            $u = $this->bind_params = [];
            if (is_array ($params)) {
                if (AUTO_UPDATE_TIME and !isset($params['created_at'])
                    and $this->do[$statement_id] === 'insert') {
                    $params['created_at'] = TIMESTAMP;
                }
                if (AUTO_UPDATE_TIME and !isset($params['updated_at'])
                    and ($this->do[$statement_id] === 'insert'
                    or $this->do[$statement_id] === 'update')) {
                    array_splice(
                        $params,
                        $this->column_count[$statement_id],
                        0,
                        [TIMESTAMP]
                    );
                }

                foreach ($params as $k => $v) {
                    if ($k === 0) {
                        // array_spliceで入れたupdated_atはキーが0になるためキー名を変える
                        $k = 'updated_at';
                    }
                    $d_v = (strlen($v) > 5000) ? '[longtext or binary]' : $v;

                    $name = $this->name[$statement_id] ? $k : $i;
                    if ($this->debug) {
                        if ($d_v === null) {
                            $this->disp_sql .= sprintf(
                                "{{COUNTER %d}}SET {{AT}}@%s = {{NULL}}NULL;\n",
                                $g_counter,
                                $name
                            );
                        } else if (is_numeric($d_v)) {
                            $this->disp_sql .= sprintf(
                                "{{COUNTER %d}}SET {{AT}}@%s = {{INT}}%d;\n",
                                $g_counter,
                                $name,
                                $d_v
                            );
                        } else {
                            $this->disp_sql .= sprintf(
                                "{{COUNTER %d}}SET {{AT}}@%s = {{STRING}}'%s';\n",
                                $g_counter,
                                $name,
                                $d_v
                            );
                        }
                        $g_counter ++;
                    }
                    $this->bind_params[$name] = $v;

                    $this->stmt[$statement_id]->bindValue(
                        $name,
                        $v,
                        is_int($v) ? \PDO::PARAM_INT : \PDO::PARAM_STR
                    );
                    $u[] = sprintf('{{AT}}@%s', $name);
                    $i ++;
                }
            }

            $this->stmt[$statement_id]->execute();
            $qt = $this->after($this->bind_params);
            $this->bind_params = [];
            $count = $this->stmt[$statement_id]->rowCount();

            if ($this->debug) {
                //デバッグ表示
                $using = count($u) ? sprintf('USING %s', implode(', ', $u)) : '';
                $this->disp_sql .= sprintf(
                    "{{COUNTER %d}}EXECUTE {{STATEMENT}}%s %s;"
                    . " {{TIME}} (%s秒) [行数 %d]\n",
                    $g_counter,
                    $statement_id,
                    $using,
                    $qt,
                    $count
                );
                $g_counter ++;
            }
            return $count;
        } catch (\PDOException $e) {
            $this->dbLog($e->getMessage());
        }
    }


    /**
     * ステートメントの結合と抽出
     * @param array $param 結合するパラメータ
     * @param string $statement_id プリペアドステートメントID
     * @param bool $class_flag クラスを取得する場合TRUE
     * @return array|bool
     */
    public function bindSelect(
        array $param = [],
        string $statement_id = 'stmt',
        bool $class_flag = false
    ) {
        try {
            $count = $this->bind($param, $statement_id);
            $rows = false;
            if ($count and $class_flag) {
                $this->stmt[$statement_id]->setFetchMode(
                    \PDO::FETCH_CLASS,
                    'stdClass'
                );
            } else if ($count) {
                $this->stmt[$statement_id]->setFetchMode(
                    \PDO::FETCH_ASSOC
                );
                $rows = $this->stmt[$statement_id]->fetchAll();
            }
            if ($this->debug) {
                $this->dbSelectDump($rows);
            }
            return $rows;
        } catch (\PDOException $e) {
            $this->dbLog($e->getMessage());
        }
    }


    /**
     * プリペアドステートメントの解放
     * @global int $g_counter
     * @param string $statement_id プリペアドステートメントID
     * @throws \Error
     */
    public function stmtClose(string $statement_id = 'stmt'): void
    {
        try {
            if (!$this->stmt[$statement_id]) {
                throw new \Error('No Statement');
            }
            
            $this->stmt[$statement_id]->closeCursor();
            
            if ($this->debug) {
                global $g_counter;
                $this->disp_sql .= sprintf(
                    "{{COUNTER %d}}DEALLOCATE PREPARE {{STATEMENT}}%s;\n",
                    $g_counter,
                    $statement_id
                );
                $g_counter ++;
            }
            
            unset($this->do[$statement_id]);
            unset($this->name[$statement_id]);
        } catch (\PDOException $e) {
            $this->dbLog($e->getMessage());
        }
    }


    /**
     * AUTO_INCREMENTで最後に作成した番号を返す
     * @return int
     */
    public function getId(): int
    {
        try {
            $res = $this->connect->lastInsertId();
            if (!$res) {
                throw new \Error('GET ID ERROR');
            }
            return $res;
        } catch (\PDOException $e) {
            $this->dbLog($e->getMessage());
        }
    }


    /**
     * テーブル排他ロック
     * （ロック中のテーブルは別の人は更新できない）
     * @params string $tables ロックするテーブル（カンマ区切り）
     */
    public function lock(string $tables): void
    {
        // トランザクション使用中は実行できない。
        if ($this->transaction_flag) {
            throw new \Error('LOCK ERROR');
        }

        $this->lock_flag = true;
        $this->query(
            sprintf(
                'LOCK TABLES %s WRITE',
                preg_replace('/,/', ' WRITE,', $tables)
            )
        );
    }


    /**
     * テーブル排他ロック解除
     */
    public function unlock(): void
    {
        if ($this->lock_flag) {
            $this->lock_flag = false;
            $this->query('UNLOCK TABLES');
        }
    }


    /**
     * トランザクションの開始
     * @global int $g_counter
     * @return bool
     */
    public function transaction(): bool
    {
        try {
            $res = false;
            if ($this->debug and !$this->transaction_flag) {
                global $g_counter;
                $this->disp_sql .= "{{COUNTER " . $g_counter . "}}"
                    . "START TRANSACTION;\n";
                $g_counter ++;
            }
            if (!$this->transaction_flag) {
                $this->transaction_flag = true;
                $res = $this->connect->beginTransaction();
            }
            return $res;
        } catch (\PDOException $e) {
            $this->dbLog($e->getMessage());
        }
    }


    /**
     * トランザクションの確定
     * @global int $g_counter
     * @return bool
     */
    public function commit(): bool
    {
        try {
            $res = false;
            if ($this->debug and $this->transaction_flag) {
                global $g_counter;
                $this->disp_sql .= "{{COUNTER " . $g_counter . "}}COMMIT;\n";
                $g_counter ++;
            }
            if ($this->transaction_flag) {
                $this->transaction_flag = false;
                $res = $this->connect->commit();
            }
            return $res;
        } catch (\PDOException $e) {
            $this->dbLog($e->getMessage());
        }
    }

    /**
     * トランザクションの復帰
     * @global int $g_counter
     * @return bool
     */
    public function rollback(): bool
    {
        try {
            $res = false;
            if ($this->debug and $this->transaction_flag) {
                global $g_counter;
                $this->disp_sql .= "{{COUNTER " . $g_counter . "}};ROLLBACK\n";
                $g_counter ++;
            }
            if ($this->transaction_flag) {
                $this->transaction_flag = false;
                $res = $this->connect->rollBack();
            }
            return $res;
        } catch (\PDOException $e) {
            $this->dbLog($e->getMessage());
        }
    }
    
    /**
     * ルーチンの呼び出し
     * @param string $name ルーチンの名前
     * @param mixed $param パラメータ
     * @param string $statement_id ステートメントID
     * @return bool
     */
    public function call(
        string $name,
        array $param,
        string $statement_id = 'stmt'
    ): bool {
        try {
            $params = implode(', ', $param);
            $res = $this->query(
                sprintf('CALL %s(%s)', $name, $params),
                null,
                $statement_id
            );
            return $res;
        } catch (\PDOException $e) {
            $this->dbLog($e->getMessage());
        }
    }
}
