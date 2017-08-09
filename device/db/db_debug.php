<?php
/**
 * データベースのデバッグ
 *
 * @author   Sawada Hideshige
 * @version  1.0.0.0
 * @package  device/db
 *
 */

namespace Php\Framework\Device\Db;

use Php\Framework\Device\Log;

trait DbDebug
{
    private $debug; // デバッグフラグ
    private $time; // ステートメント開始時間
    private $qt_sum = 0; // 実行時間合計
    private $disp_sql = ''; // デバッグ表示用に成型したSQL
    
    /**
     * 実行したSQLログを取得
     * @param bool $debug_flag
     * @return string
     */
    public function getSql(bool $debug_flag): string
    {
        return $debug_flag ? $this->disp_sql : $this->sql;
    }
    
    /**
     * 抽出されたデータをデバッグに表示
     * @param array $rows
     * @return void
     */
    private function dbSelectDump(array $rows): void
    {
        if ($rows and $this->debug) {
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
                is_numeric($v) ? $v : (is_null($v) ? 'NULL' : "'" . $v . "'"));
        }
    }

    /**
     * 実行時間の測定開始
     * @return void
     */
    private function before(): void
    {
        if ($this->debug) {
            $this->time = microtime(true);
        }
    }

    /**
     * 実行時間の取得
     * @return float 実行時間
     */
    private function after(): float
    {
        $qt = 0;
        if ($this->debug) {
            $t = microtime(true);
            $qt = round($t - $this->time, 4);
            $this->qt_sum += $qt;
            if ($qt > 5) {
                // スロークエリの記録
                Log::error(sprintf('[SLOW QUERY] %s [PARAM] %s (%s)',
                    $this->sql, implode(',', $this->bind_params), $qt));
            }
        }
        return $qt;
    }
    
    /**
     * エラーメッセージの成型
     * @param string $error
     * @return void
     * @throws \Error
     */
    private function dbLog(string $class_name, string $error): void
    {
        $bind = [];
        if ($this->bind_params) {
            foreach ($this->bind_params as $k => $v) {
                $bind[] = '@' . $k . " = '" . $v . "'"; 
            }
        }
        $error_mes = sprintf("%s: %s\n[QUERY] %s;\n[PARAM] %s",
            $class_name, $error, $this->sql, implode(',', $bind));
        throw new \Error($error_mes);
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
        if ($this->debug) {
            // 実行したSQL文と実行時間、変更行数
            global $g_counter;
            $this->disp_sql .= sprintf(
                "{{COUNTER %d}}%s; {{TIME}} (%s秒) [行数 %d]\n",
                $g_counter, $this->sql, $qt,
                $this->stmt[$statement_id]->rowCount());
            $g_counter ++;
        }
    }
    
    /**
     * バインドのデバッグ表示
     * @global int $g_counter
     * @param string|int $name
     * @param string|int|null $value
     * @return void
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
     * 処理実行のデバッグ表示
     * @global int $g_counter
     * @param string $statement_id
     * @return void
     */
    private function executeDebug(string $statement_id): void
    {
        if ($this->debug) {
            global $g_counter;
            $this->disp_sql .= sprintf(
                "{{COUNTER %d}}EXECUTE {{STATEMENT}}%s %s;\n",
                $g_counter, $statement_id, $this->debugUsing());
            $g_counter ++;
        }
    }
    
    /**
     * 処理実行の時間と件数
     * @param int $count
     * @return void
     */
    private function executeDebugCount(int $count): void
    {
        if ($this->debug) {
            $this->disp_sql .= sprintf("{{TIME}} (%s秒) [行数 %d]\n",
                $this->after(), $count);
        }
    }
}
