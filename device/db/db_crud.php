<?php
/**
 * データベース（CRUD、トランザクション関連）
 *
 * @author   Sawada Hideshige
 * @version  1.0.0.1
 * @package  device/db
 *
 */

namespace Php\Framework\Device\Db;

class DbCrud extends DbStatement
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
            $this->addTimeColumn('insert', $params, $statement_id, false);
            $params_keys = array_keys($params);
            foreach ($params_keys as $v) {
                $params[$v] = ':' . $v;
            }
            $fields = implode(', ', $params_keys);
            $values = implode(', ', $params);

            $command = $replace ? 'REPLACE' : 'INSERT';
            $this->sql = sprintf('%s INTO %s (%s) VALUES (%s)',
                $command, $table, $fields, $values);

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
        if (!isset($this->name[$statement_id])) {
            $this->name[$statement_id] = 
                preg_match('/\?/', $where) ? false : true;
        }

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
     * @return object|bool|void
     */
    public function update(
        string $table,
        array $params,
        string $where = '',
        string $statement_id = 'stmt'
    ) {
        $this->do[$statement_id] = 'update';
        
        // プレースホルダが?か:nameかを判定
        if (!isset($this->name[$statement_id])) {
            $this->name[$statement_id] = 
                preg_match('/\?/', $where) ? false : true;
        }

        if (is_array($params)) {
            $this->addTimeColumn('update', $params, $statement_id, false);
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

            return $this->prepare($statement_id);
        }
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
        if (!isset($this->name[$statement_id])) {
            $this->name[$statement_id] = 
                preg_match('/\?/', $where) ? false : true;
        }

        $this->sql = sprintf('DELETE FROM %s %s', $table, $where);

        $res = $this->prepare($statement_id);
        return $res;
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
            $this->dbLog('getId', $e->getMessage());
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
            $this->dbLog('transaction', $e->getMessage());
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
            $this->dbLog('commit', $e->getMessage());
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
                $this->disp_sql .= "{{COUNTER " . $g_counter . "}}ROLLBACK;\n";
                $g_counter ++;
            }
            if ($this->transaction_flag) {
                $this->transaction_flag = false;
                $res = $this->connect->rollBack();
            }
            return $res;
        } catch (\PDOException $e) {
            $this->dbLog('rollback', $e->getMessage());
        }
    }
}
