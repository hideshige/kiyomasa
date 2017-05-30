<?php
/**
 * データベース（接続、クエリ関連）
 *
 * @author   Sawada Hideshige
 * @version  1.0.2.0
 * @package  device/db
 *
 */

namespace Php\Framework\Device\Db;

class DbModule
{
    protected $connect; // データベースオブジェクト
    protected $stmt = []; // ステートメント
    protected $do = []; // ステートメントで実行中の動作メモを格納
    protected $name = []; // ステートメントで実行中のプレースホルダが名前の場合TRUE
    protected $column_count = []; // 更新するカラムの数
    protected $bind_params = []; // バインドする値
    protected $time; // ステートメント開始時間
    protected $sql = ''; // 実行するSQL
    public $debug; // デバッグフラグ
    public $disp_sql = ''; // 整形後の画面表示用SQL
    public $transaction_flag = false; // トランザクション実行中の場合TRUE
    public $lock_flag = false; // テーブル排他ロック中の場合TRUE
    public $qt_sum = 0; // 実行時間合計

    /**
     * 接続
     * @param string $db_server サーバーの名前
     * @param string $db_user ユーザーの名前
     * @param string $db_password ユーザーのパスワード
     * @param string $db_name データベースの名前
     * @param string $db_soft 使用するDBソフト
     * @return bool 成否
     * @throws \PDOException
     */
    public function connect(
        string $db_server, 
        string $db_user, 
        string $db_password, 
        string $db_name, 
        string $db_soft = 'mysql'
    ): bool {
        try {
            $res = true;
            $dsn = sprintf('%s:host=%s;dbname=%s',
                $db_soft, $db_server, $db_name);
            $this->connect = new \PDO ($dsn, $db_user, $db_password,
                [\PDO::ATTR_PERSISTENT => false,
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
            $this->query(sprintf("SET NAMES '%s'", DEFAULT_CHARSET));
            $query = $this->query("SET sql_mode = 'STRICT_TRANS_TABLES,"
                . " NO_ZERO_IN_DATE, NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO'");
            if (!$query) {
                throw new \Error('SQL MODE ERROR');
            }
        } catch (\PDOException $e) {
            Log::error($e->getMessage());
            $res = false;
        } finally {
            return $res;
        }
    }

    /**
     * 実行時間の測定開始
     */
    protected function before(): void
    {
        $this->time = microtime(true);
    }

    /**
     * 実行時間の取得
     * @return float 実行時間
     */
    protected function after(): float
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
     * @throws \Error
     */
    protected function dbLog(string $error): void
    {
        $bind = [];
        if ($this->bind_params) {
            foreach ($this->bind_params as $k => $v) {
                $bind[] = '@' . $k . " = '" . $v . "'"; 
            }
        }
        $error_mes = sprintf(
            "%s\n[QUERY] %s;\n[PARAM] %s",
            $error,
            $this->sql,
            implode(',', $bind)
        );
        throw new \Error($error_mes);
    }
    
    /**
     * 抽出されたデータをデバッグに表示
     * @param array $rows
     */
    function dbSelectDump(array $rows): void
    {
        if ($rows) {
            $this->disp_sql .= '═══ BEGIN ROW ═══';
            foreach ($rows as $row_k => $row) {
                if ($row_k > 3) {
                    $this->disp_sql .= "═══ and more... ═══\n";
                    break;
                }
                if (count($rows) > 1) {
                    $this->disp_sql .= "═══ $row_k ═══\n";
                }
                foreach ($row as $k => $v) {
                    $this->disp_sql .= sprintf(
                        "'%s' : %s\n", $k, is_numeric($v) ? $v : "'" . $v . "'"
                    );
                }
            }
            $this->disp_sql .= '═══ END ROW ═══';
        }
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

            $this->queryDebug($qt);
            return $this->stmt[$statement_id];
        } catch (\PDOException $e) {
            $this->dbLog($e->getMessage());
        }
    }
    
    /**
     * クエリの内容をデバッグに表示
     * @global int $g_counter
     * @param float $qt
     * @param string $statement_id
     */
    private function queryDebug(float $qt, string $statement_id = 'stmt')
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
    
    /**
     * 時刻のカラムに自動で登録
     * @param string $type
     * @param array $params
     * @param string $statement_id
     */
    protected function addTimeColumn(
        string $type,
        array &$params,
        string $statement_id,
        bool $bind_flag = true
    ): void {
        if ($type === 'insert' and
            AUTO_UPDATE_TIME and !isset($params['created_at'])) {
            $params['created_at'] = TIMESTAMP;
        }
        if (($type === 'insert' or $type === 'update') and
            AUTO_UPDATE_TIME and !isset($params['updated_at'])) {
            $this->column_count[$statement_id] = count($params);
            if (!$bind_flag or $type === 'insert') {
                $params['updated_at'] = TIMESTAMP;
            } else {
                array_splice(
                    $params,
                    $this->column_count[$statement_id],
                    0,
                    [TIMESTAMP]
                );
            }
        }
    }
}
