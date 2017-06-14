<?php
/**
 * データベース(プリペアドステートメント関連)
 *
 * @author   Sawada Hideshige
 * @version  1.4.5.3
 * @package  device/db
 *
 */

namespace Php\Framework\Device\Db;

class DbStatement extends DbModule
{
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
            if (!isset($this->do[$statement_id])) {
                $this->do[$statement_id] = 'prepare';
            }
            
            global $g_counter;
            if ($sql) {
                $this->sql = $sql;
            }

            $this->stmt[$statement_id] = $this->connect->prepare($this->sql);

            if ($this->debug) {
                $this->disp_sql .= sprintf(
                    "{{COUNTER %d}}PREPARE {{STATEMENT}}%s FROM '%s';\n",
                    $g_counter, $statement_id,
                    preg_replace("/'/", "\\'", $this->sql));
                $g_counter ++;
            }

            return $this->stmt[$statement_id];
        } catch (\PDOException $e) {
            $this->dbLog('prepare', $e->getMessage());
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
            $this->before();

            if (!isset($this->name[$statement_id])) {
                $this->name[$statement_id] = true;
            }

            $this->bindValueSet($params, $statement_id);

            $res = $this->stmt[$statement_id]->execute();
            if (!$res) {
                throw new \Error('Bind Error');
            }
            $count = $this->stmt[$statement_id]->rowCount();
            $this->executeDebug($statement_id, $count);
            return $count;
        } catch (\PDOException $e) {
            $this->dbLog('bind', $e->getMessage());
        }
    }
    
    /**
     * バインドの値をセット
     * @param array $params
     * @param string $statement_id
     */
    private function bindValueSet(array $params, string $statement_id): void
    {
        $this->bind_params = [];
        $this->addTimeColumn(
            $this->do[$statement_id], $params, $statement_id, true);
        
        $i = 1;
        foreach ($params as $k => $v) {
            if ($k === 0 and $this->do[$statement_id] === 'update') {
                // array_spliceで入れた0の配列キーをupdated_atに変える
                $k = 'updated_at';
            }
            $name = $this->name[$statement_id] ? $k : $i;
            
            $this->bind_params[$name] = $v;
            $this->bindDebug($name, $v);

            $this->stmt[$statement_id]->bindValue($name, $v,
                is_int($v) ? \PDO::PARAM_INT : \PDO::PARAM_STR);
            $i ++;
        }
    }
    
    /**
     * バインドのデバッグ表示
     * @global int $g_counter
     * @param string|int $name
     * @param string|int|null $value
     */
    private function bindDebug($name, $value): void
    {
        if ($this->debug) {
            $d_v = (strlen($value) > 5000) ? '[longtext or binary]' : $value;
            global $g_counter;
            if ($d_v === null) {
                $this->disp_sql .= sprintf(
                    "{{COUNTER %d}}SET {{AT}}@%s = {{NULL}}NULL;\n",
                    $g_counter, $name);
            } else if (is_numeric($d_v)) {
                $this->disp_sql .= sprintf(
                    "{{COUNTER %d}}SET {{AT}}@%s = {{INT}}%d;\n",
                    $g_counter, $name, $d_v);
            } else {
                $this->disp_sql .= sprintf(
                    "{{COUNTER %d}}SET {{AT}}@%s = {{STRING}}'%s';\n",
                    $g_counter, $name, $d_v);
            }
            $g_counter ++;
        }
    }
    
    /**
     * 処理実行のデバッグ表示
     * @global int $g_counter
     * @param string $statement_id
     * @param int $count
     */
    private function executeDebug(
        string $statement_id,
        int $count
    ): void {
        if ($this->debug) {
            global $g_counter;
            $this->disp_sql .= sprintf(
                "{{COUNTER %d}}EXECUTE {{STATEMENT}}%s %s;"
                . " {{TIME}} (%s秒) [行数 %d]\n",
                $g_counter,
                $statement_id,
                $this->debugUsing(),
                $this->after(),
                $count
            );
            $g_counter ++;
        }
    }
    
    /**
     * デバッグのUSING表示用
     * @return string
     */
    private function debugUsing(): string
    {
        $using = '';
        if (count($this->bind_params)) {
            $using .= 'USING ';
            $using_arr = [];
            $bind_arr = array_keys($this->bind_params);
            foreach ($bind_arr as $v) {
                $using_arr[] = '{{AT}}@' . $v;
            }
            $using .= implode(', ', $using_arr);
            $this->bind_params = [];
        }
        return $using;
    }
    
    /**
     * ステートメントの結合と一括抽出
     * （これは全件を一挙にフェッチするため負荷に注意する）
     * @param array $param 結合するパラメータ
     * @param string $statement_id プリペアドステートメントID
     * @param bool $class_flag 配列の中にクラスオブジェクトを入れる場合TRUE
     * @return array
     */
    public function bindFetchAll(
        array $param = [],
        string $statement_id = 'stmt',
        bool $class_flag = false
    ): array {
        try {
            $count = $this->bind($param, $statement_id);
            if ($count and $class_flag) {
                $this->stmt[$statement_id]->setFetchMode(
                    \PDO::FETCH_CLASS, 'stdClass');
            } else if ($count) {
                $this->stmt[$statement_id]->setFetchMode(\PDO::FETCH_ASSOC);
            }
            $rows = $this->stmt[$statement_id]->fetchAll();
            if ($rows === false) {
                throw new \Error('Fetch Error');
            }
            $this->dbSelectDump($rows);
            return $rows;
        } catch (\PDOException $e) {
            $this->dbLog('bindFetchAll', $e->getMessage());
        }
    }
    
    /**
     * 1行フェッチ
     * @param string $class_name クラスオブジェクトとして取得する場合指定する
     * @param string $statement_id プリペアドステートメントID
     * @return object|array|bool
     */
    public function fetch(
        string $class_name = '',
        string $statement_id = 'stmt'
    ) {
        try {
            if ($class_name) {
                $rows = $this->stmt[$statement_id]->fetchObject($class_name);
            } else {
                $rows = $this->stmt[$statement_id]->fetch(\PDO::FETCH_ASSOC);
            }
            if ($rows and $this->debug) {
                // デバッグ表示
                $this->disp_sql .= '═══ BEGIN ROW ═══';
                $this->dbSelectDumpDetail($rows);
                $this->disp_sql .= "═════════\n";
                $this->disp_sql .= '═══ END ROW ═══';
            }
            return $rows;
        } catch (\PDOException $e) {
            $this->dbLog('fetch', $e->getMessage());
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
            unset($this->do[$statement_id]);
            unset($this->name[$statement_id]);
            
            if ($this->debug) {
                global $g_counter;
                $this->disp_sql .= sprintf(
                    "{{COUNTER %d}}DEALLOCATE PREPARE {{STATEMENT}}%s;\n",
                    $g_counter, $statement_id);
                $g_counter ++;
            }
        } catch (\PDOException $e) {
            $this->dbLog('stmtClose', $e->getMessage());
        }
    }
}
