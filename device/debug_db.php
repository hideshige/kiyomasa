<?php
/**
 * データベース モジュール（デバッグ用）
 *
 * @author   Sawada Hideshige
 * @version  2.1.10.0
 * @package  device
 * 
 */

namespace Php\Framework\Device;

class DebugDb extends Db
{
    protected array $bind_params = []; // バインドする値（デバッグ表示およびログ用）
    private string $disp_sql = ''; // デバッグ表示用SQL
    private string $time; // ステートメント開始時間
    private float $qt_sum = 0; // 実行時間合計
    
    /**
     * 接続
     * @global int $g_counter
     * @return bool 成否
     */
    public function connect(): bool {
        global $g_counter;
        $this->disp_sql .= sprintf("{{COUNTER %d Connected}}\n", $g_counter);
        $g_counter ++;
        return parent::connect();
    }
    
    /**
     * 実行したSQLログを取得
     * @return string
     */
    public function getSql(): string
    {
        return $this->disp_sql;
    }
    
    /**
     * 抽出されたデータをデバッグに表示
     * @param array $rows
     * @return void
     */
    private function dbSelectDump(array $rows): void
    {
        if ($rows) {
            $this->disp_sql .= '═══ BEGIN ROW ═══';
            foreach ($rows as $row_k => $row_v) {
                if ($row_k > 3) {
                    $this->disp_sql .= "═══ and more... ═══\n";
                    break;
                }
                $this->disp_sql .= (count($rows) > 1) ? "═══ $row_k ═══\n" : '';
                $this->dbSelectDumpDetail($row_v);
            }
            $this->disp_sql .= '═══ END ROW ═══';
        }
    }
    
    /**
     * 抽出されたデータの詳細
     * @param array|object $rows
     * @return void
     */
    private function dbSelectDumpDetail($rows): void
    {
        foreach ($rows as $k => $v) {
            $this->disp_sql .= sprintf("'%s' : %s\n", $k,
                is_numeric($v) ? $v : 
                (is_null($v) ? 'NULL' : 
                (is_array($v) ? json_encode($v) : "'" . $v . "'")));
        }
    }

    /**
     * 実行時間の測定開始
     * @return void
     */
    private function before(): void
    {
        $this->time = microtime(true);
    }

    /**
     * 実行時間の取得
     * @return float 実行時間
     */
    private function after(): float
    {
        $qt = 0;
        $t = microtime(true);
        $qt = round($t - $this->time, 4);
        $this->qt_sum += $qt;
        if ($qt > 5) {
            // スロークエリの記録
            Log::error(sprintf('[SLOW QUERY] %s [PARAM] %s (%s)',
                $this->sql, implode(',', $this->bind_params), $qt));
        }
        return $qt;
    }
    
    /**
     * クエリの内容をデバッグに表示
     * @global int $g_counter
     * @param string $statement_id
     * @param float $qt
     * @return void
     */
    private function queryDebug(string $statement_id, float $qt): void
    {
        // 実行したSQL文と実行時間、変更行数
        global $g_counter;
        $this->disp_sql .= sprintf(
            "{{COUNTER %d}}%s; {{TIME}} (%s秒) [行数 %d]\n",
            $g_counter, $this->sql, $qt,
            $this->stmt[$statement_id]->rowCount());
        $g_counter ++;
    }
    
    /**
     * バインドのデバッグ表示
     * @global int $g_counter
     * @param string|int $name
     * @param string|int|null $value
     * @return void
     */
    protected function bindDebug($name, $value): void
    {
        $d_v = (strlen($value ?? 0) > 5000) ? '[longtext or binary]' : $value;
        global $g_counter;
        if ($d_v === null) {
            $this->disp_sql .= sprintf(
                "{{COUNTER %d}}SET {{AT}}@%s = {{NULL}}NULL;\n",
                $g_counter, $name);
        } else if (is_numeric($d_v)) {
            $this->disp_sql .= sprintf(
                "{{COUNTER %d}}SET {{AT}}@%s = {{INT}}%s;\n",
                $g_counter, $name, $d_v);
        } else {
            $this->disp_sql .= sprintf(
                "{{COUNTER %d}}SET {{AT}}@%s = {{STRING}}'%s';\n",
                $g_counter, $name, $d_v);
        }
        $g_counter ++;
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
        }
        return $using;
    }
    
    /**
     * 処理実行のデバッグ表示
     * @global int $g_counter
     * @param string $statement_id
     * @return void
     */
    protected function executeDebug(string $statement_id): void
    {
        global $g_counter;
        $this->disp_sql .= sprintf(
            "{{COUNTER %d}}EXECUTE {{STATEMENT}}%s %s;\n",
            $g_counter, $statement_id, $this->debugUsing());
        $g_counter ++;
    }
    
    /**
     * 処理実行の時間と件数
     * @param int $count
     * @return void
     */
    protected function executeDebugCount(int $count): void
    {
        $this->disp_sql .= sprintf("{{TIME}} (%s秒) [行数 %d]\n",
            $this->after(), $count);
    }
    
    /**
     * エラーメッセージの成型
     * @param string $class_name
     * @param string $error
     * @return void
     * @throws \Error
     */
    protected function dbLog(string $class_name, string $error): void
    {
        $bind = [];
        if ($this->bind_params) {
            foreach ($this->bind_params as $k => $v) {
                $bind[] = '@' . $k . " = '" . $v . "'"; 
            }
        }
        $error_mes = sprintf("%s: %s\n[QUERY] %s;\n[PARAM] %s",
            $class_name, $error, $this->sql, implode(',', $bind));
        trace();
        throw new \Error($error_mes);
    }
    
    /**
     * クエリ
     * @param string $sql
     * @param string $statement
     * @return \PDOStatement
     */
    public function query(
        string $sql,
        string $statement = 'stmt'
    ): \PDOStatement {
        $this->sql = $sql;
        $this->before();
        $res = parent::query($sql, $statement);
        $this->queryDebug($statement, $this->after());
        return $res;
    }
    
    /**
     * 実行
     * @param string $sql
     * @return int
     */
    public function exec(string $sql): int
    {
        $this->before();
        $res = parent::exec($sql);
        global $g_counter;
        $this->disp_sql .= sprintf(
            "{{COUNTER %d}}%s;\n{{TIME}} (%s秒) [行数 %d]\n",
            $g_counter, $sql, $this->after(), $res);
        $g_counter ++;
        return $res;
    }
    
    /**
     * 隔離性水準の設定
     * @param int $level
     * @return void
     */
    public function setIsolationLevel(int $level): void
    {
        parent::setIsolationLevel($level);
        global $g_counter;
        $this->disp_sql .= "{{COUNTER " . $g_counter . "}}"
            . "SET TRANSACTION ISOLATION LEVEL "
            . ($this->islv[$level] ?? 'REPEATABLE READ') . ";\n";
        $g_counter ++;
    }
    
    /**
     * トランザクションの開始
     * @global int $g_counter
     * @return void
     */
    public function transaction(): void
    {
        if ($this->transaction_flag === false) {
            global $g_counter;
            $this->disp_sql .= "{{COUNTER " . $g_counter . "}}"
                . "START TRANSACTION;\n";
            $g_counter ++;
        }
        parent::transaction();
    }
    
    /**
     * トランザクションの確定
     * @global int $g_counter
     * @return void
     */
    public function commit(): void
    {
        if ($this->transaction_flag) {
            global $g_counter;
            $this->disp_sql .= "{{COUNTER " . $g_counter . "}}COMMIT;\n";
            $g_counter ++;
        }
        parent::commit();
    }
    
    /**
     * トランザクションの復帰
     * @global int $g_counter
     * @return void
     */
    public function rollback(): void
    {
        if ($this->transaction_flag) {
            global $g_counter;
            $this->disp_sql .= "{{COUNTER " . $g_counter . "}}ROLLBACK;\n";
            $g_counter ++;
        }
        parent::rollback();
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
        global $g_counter;
        if ($sql) {
            $this->sql = $sql;
        }

        $this->disp_sql .= sprintf(
            "{{COUNTER %d}}PREPARE {{STATEMENT}}%s FROM '%s';\n",
            $g_counter, $statement_id, $this->sql);
        $g_counter ++;
        return parent::prepare($statement_id, $this->sql);
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
        $this->before();
        $count = parent::bind($params, $statement_id);
        return $count;
    }
    
    /**
     * ステートメントの結合と一括抽出（配列として）
     * （これは全件を一挙にフェッチするため負荷に注意する）
     * @param array $param 結合するパラメータ
     * @param string $statement_id プリペアドステートメントID
     * @return array
     */
    public function bindFetchAll(
        array $param = [],
        string $statement_id = 'stmt'
    ): array {
        $rows = parent::bindFetchAll($param, $statement_id);
        $this->dbSelectDump($rows);
        return $rows;
    }
    
    /**
     * ステートメントの結合と一括抽出（クラスとして）
     * （これは全件を一挙にフェッチするため負荷に注意する）
     * @param array $param 結合するパラメータ
     * @param string $statement_id プリペアドステートメントID
     * @return array
     */
    public function bindFetchAllClass(
        array $param = [],
        string $statement_id = 'stmt'
    ): array {
        $rows = parent::bindFetchAllClass($param, $statement_id);
        $this->dbSelectDump($rows);
        return $rows;
    }
    
    /**
     * 1行フェッチ
     * @param string $statement_id プリペアドステートメントID
     * @return array|bool
     */
    public function fetch(string $statement_id = 'stmt')
    {
        $rows = parent::fetch($statement_id);
        if ($rows) {
            // デバッグ表示
            $this->disp_sql .= '═══ BEGIN ROW ═══';
            $this->dbSelectDumpDetail($rows);
            $this->disp_sql .= '═══ END ROW ═══';
        }
        return $rows;
    }
    
    /**
     * 1行フェッチクラス
     * @param string $class_name クラスオブジェクトとして取得する場合指定する
     * @param string $statement_id プリペアドステートメントID
     * @return array|bool
     */
    public function fetchClass(
        string $class_name,
        string $statement_id = 'stmt'
    ) {
        $rows = parent::fetchClass($class_name, $statement_id);
        if ($rows) {
            // デバッグ表示
            $this->disp_sql .= '═══ BEGIN ROW ═══';
            $this->dbSelectDumpDetail($rows);
            $this->disp_sql .= "═════════\n";
            $this->disp_sql .= '═══ END ROW ═══';
        }
        return $rows;
    }
    
    /**
     * プリペアドステートメントの解放
     * @global int $g_counter
     * @param string $statement_id プリペアドステートメントID
     * @return void
     * @throws \Error
     */
    public function stmtClose(string $statement_id = 'stmt'): void
    {
        parent::stmtClose($statement_id);

        global $g_counter;
        $this->disp_sql .= sprintf(
            "{{COUNTER %d}}DEALLOCATE PREPARE {{STATEMENT}}%s;\n",
            $g_counter, $statement_id);
        $g_counter ++;
    }
}
