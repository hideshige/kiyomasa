<?php
/**
 * データベース（CRUD、トランザクション関連）
 *
 * @author   Sawada Hideshige
 * @version  1.0.2.0
 * @package  device/db
 *
 */

namespace Php\Framework\Device\Db;

trait DbCrud
{
    private $column_count = []; // 更新するカラムの数
    private $bind_params = []; // バインドする値（デバッグ表示およびログ用）
    private $stmt = []; // ステートメント
    private $do = []; // ステートメントで実行中の動作メモを格納
    private $name = []; // プレースホルダが名前の場合TRUE
    
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
    ): void {
        $this->connectCheck();
        $this->do[$statement_id] = 'insert';
        $this->name[$statement_id] = true;

        if (!empty($params)) {
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

            $this->prepare($statement_id);
        }
    }

    /**
     * 抽出
     * @param string $table テーブル名
     * @param string $params 取り出す値
     * @param string $where 取り出す条件
     * @param string $statement_id プリペアドステートメントID
     * @return void
     */
    public function select(
        string $table,
        string $params = '*',
        string $where = '',
        string $statement_id = 'stmt'
    ): void {
        $this->connectCheck();
        $this->do[$statement_id] = 'select';
        
        // プレースホルダが?か:nameかを判定
        $this->name[$statement_id] =
            strpos($where, '?') !== false ? false : true;

        $this->sql = sprintf('SELECT %s FROM %s %s', $params, $table, $where);
        $this->prepare($statement_id);
    }


    /**
     * 更新
     * @param string $table テーブル名
     * @param array $params 更新する値（配列からフィールド名とフィールド値を取り出す）
     * @param string $where 検索条件
     * @param string $statement_id プリペアドステートメントID
     * @return void
     */
    public function update(
        string $table,
        array $params,
        string $where = '',
        string $statement_id = 'stmt'
    ): void {
        $this->connectCheck();
        $this->do[$statement_id] = 'update';
        
        // プレースホルダが?か:nameかを判定
        $this->name[$statement_id] =
            strpos($where, '?') !== false ? false : true;

        if (!empty($params)) {
            $this->addTimeColumn('update', $params, $statement_id, false);
            $values = [];
            $i = 0;
            $params_keys = array_keys($params);
            foreach ($params_keys as $key) {
                $var = $this->name[$statement_id] ? ':' . $key : '?';
                $values[$i] = sprintf('%s = %s', $key, $var);
                $i ++;
            }
            $value = implode(', ', $values);

            $this->sql = sprintf('UPDATE %s SET %s %s', $table, $value, $where);

            $this->prepare($statement_id);
        }
    }

    /**
     * 削除
     * @param string $table テーブル名
     * @param string $where 検索条件
     * @param string $statement_id プリペアドステートメントID
     * @return void
     */
    public function delete(
        string $table,
        string $where = '',
        string $statement_id = 'stmt'
    ): void {
        $this->connectCheck();
        $this->do[$statement_id] = 'delete';
        
        // プレースホルダが?か:nameかを判定
        $this->name[$statement_id] =
            strpos($where, '?') !== false ? false : true;
        
        $this->sql = sprintf('DELETE FROM %s %s', $table, $where);
        $this->prepare($statement_id);
    }
    
    /**
     * AUTO_INCREMENTで最後に作成した番号を返す
     * @return int
     */
    public function getId(): int
    {
        try {
            $res = $this->connect->lastInsertId();
            if ($res === false) {
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
     * @return void
     */
    public function transaction(): void
    {
        try {
            $this->connectCheck();
            if ($this->debug and $this->transaction_flag === false) {
                global $g_counter;
                $this->disp_sql .= "{{COUNTER " . $g_counter . "}}"
                    . "START TRANSACTION;\n";
                $g_counter ++;
            }
            if ($this->transaction_flag === false) {
                $this->transaction_flag = true;
                $this->connect->beginTransaction();
            }
        } catch (\PDOException $e) {
            $this->dbLog('transaction', $e->getMessage());
        }
    }

    /**
     * トランザクションの確定
     * @global int $g_counter
     * @return void
     */
    public function commit(): void
    {
        try {
            if ($this->debug and $this->transaction_flag) {
                global $g_counter;
                $this->disp_sql .= "{{COUNTER " . $g_counter . "}}COMMIT;\n";
                $g_counter ++;
            }
            if ($this->transaction_flag) {
                $this->transaction_flag = false;
                $this->connect->commit();
            }
        } catch (\PDOException $e) {
            $this->dbLog('commit', $e->getMessage());
        }
    }

    /**
     * トランザクションの復帰
     * @global int $g_counter
     * @return void
     */
    public function rollback(): void
    {
        try {
            if ($this->debug and $this->transaction_flag) {
                global $g_counter;
                $this->disp_sql .= "{{COUNTER " . $g_counter . "}}ROLLBACK;\n";
                $g_counter ++;
            }
            if ($this->transaction_flag) {
                $this->transaction_flag = false;
                $this->connect->rollBack();
            }
        } catch (\PDOException $e) {
            $this->dbLog('rollback', $e->getMessage());
        }
    }
    
    /**
     * 時刻のカラムに自動で登録
     * @param string $type
     * @param array $params
     * @param string $statement_id
     * @return void
     */
    private function addTimeColumn(
        string $type,
        array &$params,
        string $statement_id,
        bool $bind_flag
    ): void {
        if ($type === 'insert' and
            AUTO_UPDATE_TIME and !isset($params['created_at'])) {
            $params['created_at'] = TIMESTAMP;
        }
        if (($type === 'insert' or $type === 'update') and
            AUTO_UPDATE_TIME and !isset($params['updated_at'])) {
            if ($bind_flag === false or $type === 'insert') {
                $this->column_count[$statement_id] = count($params);
                $params['updated_at'] = TIMESTAMP;
            } else {
                array_splice($params, $this->column_count[$statement_id],
                    0, [TIMESTAMP]);
            }
        }
    }
}
