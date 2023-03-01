<?php
/**
 * データベース モジュール
 *
 * @author   Sawada Hideshige
 * @version  2.1.8.0
 * @package  device
 * 
 */

namespace Php\Framework\Device;

class Db
{
    protected string $sql = ''; // 現在実行中のSQL
    protected \PDO $connect; // PDOインスタンス
    protected string $db_server; // DBサーバ
    protected string $db_user; // DBユーザ
    protected string $db_password; // DBパスワード
    protected string $db_name; // DB名
    protected string $db_driver; // 使用するドライバ
    protected bool $connect_flag = false; // 接続されているかどうか
    protected array $stmt = []; // ステートメント
    protected array $column_count = []; // 更新するカラムの数
    protected array $do = []; // ステートメントで実行中の動作メモを格納
    protected array $name = []; // プレースホルダが名前の場合TRUE
    protected bool $transaction_flag = false; // トランザクション実行中の場合TRUE
    protected bool $lock_flag = false; // テーブル排他ロック中の場合TRUE
    protected array $islv = [
        'READ UNCOMMITTED',
        'READ COMMITTED',
        'REPEATABLE READ',
        'SERIALIZABLE'
    ]; // 隔離性水準

    /**
     * パラメータのセット
     * @param string $db_server
     * @param string $db_user
     * @param string $db_password
     * @param string $db_name
     * @param string $db_driver
     */
    public function __construct(
        string $db_server, 
        string $db_user, 
        string $db_password, 
        string $db_name, 
        string $db_driver
    ) {
        $this->db_server = $db_server;
        $this->db_user = $db_user;
        $this->db_password = $db_password;
        $this->db_name = $db_name;
        $this->db_driver = $db_driver;
    }
    
    /**
     * 接続
     * @return bool 成否
     * @throws \PDOException
     */
    public function connect(): bool {
        try {
            $res = false;
            $dsn = sprintf('%s:host=%s;dbname=%s;charset=utf8mb4',
                $this->db_driver, $this->db_server, $this->db_name);
            
            $this->connect = new \PDO($dsn, $this->db_user, $this->db_password,
                [\PDO::ATTR_PERSISTENT => false,
                \PDO::ATTR_EMULATE_PREPARES => false,
                // 次のオプションはMySQLでファイルを読み込む場合に必要
                // php.iniでmysqli.allow_local_infile = Onとなっていることが前提
                // 不要の場合はコメントアウトする
                \PDO::MYSQL_ATTR_LOCAL_INFILE => true,
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
            $this->connect_flag = true;
            $res = true;
        } catch (\PDOException $e) {
            Log::error($e->getMessage());
        } finally {
            return $res;
        }
    }
    
    /**
     * 接続の確認
     * @return void
     */
    public function connectCheck(): void
    {
        if ($this->connect_flag === false) {
            if ($this->connect() === false) {
                throw new \Error('DB CONNECT CHECK ERROR');
            }
        }
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
        // debug_db.phpで継承して実装
        throw new \Error($class_name . ' ' . $error);
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
        try {
            $this->connectCheck();
            $this->stmt[$statement] = $this->connect->query($sql);
            return $this->stmt[$statement];
        } catch (\PDOException $e) {
            $this->dbLog('query', $e->getMessage());
        }
    }
    
    /**
     * 実行
     * @param string $sql
     * @return int
     */
    public function exec(string $sql): int
    {
        try {
            $this->connectCheck();
            return $this->connect->exec($sql);
        } catch (\PDOException $e) {
            $this->dbLog('exec', $e->getMessage());
        }
    }
    
    /**
     * 作成
     * @param string $table テーブル名
     * @param array $params 挿入する値（配列からフィールド名とフィールド値を取り出す）
     * @param bool $replace 同一キーの場合置換するかどうかの可否
     * @param string $statement_id プリペアドステートメントID
     * @return void
     */
    public function insert(
        string $table,
        array $params,
        bool $replace = false,
        string $statement_id = 'stmt'
    ): void {
        if (($statement_id === 'stmt' or !isset($this->stmt[$statement_id]))
            and !empty($params)) {
            $this->connectCheck();
            $this->do[$statement_id] = 'insert';
            $this->name[$statement_id] = true;

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
        if ($statement_id === 'stmt' or !isset($this->stmt[$statement_id])) {
            $this->connectCheck();
            $this->do[$statement_id] = 'select';

            // プレースホルダが?か:nameかを判定
            $this->name[$statement_id] =
                strpos($where, '?') !== false ? false : true;

            $this->sql = sprintf('SELECT %s FROM %s %s', $params, $table, $where);
            $this->prepare($statement_id);
        }
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
        if (($statement_id === 'stmt' or !isset($this->stmt[$statement_id]))
            and !empty($params)) {
            $this->connectCheck();
            $this->do[$statement_id] = 'update';

            // プレースホルダが?か:nameかを判定
            $this->name[$statement_id] =
                strpos($where, '?') !== false ? false : true;

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
        if ($statement_id === 'stmt' or !isset($this->stmt[$statement_id])) {
            $this->connectCheck();
            $this->do[$statement_id] = 'delete';

            // プレースホルダが?か:nameかを判定
            $this->name[$statement_id] =
                strpos($where, '?') !== false ? false : true;

            $this->sql = sprintf('DELETE FROM %s %s', $table, $where);
            $this->prepare($statement_id);
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
            return $res;
        } catch (\PDOException $e) {
            $this->dbLog('getId', $e->getMessage());
        }
    }
    
    /**
     * 隔離性水準の設定
     * @param int $level
     * @return void
     */
    public function setIsolationLevel(int $level): void
    {
        try {
            $this->connectCheck();
            $this->connect->query('SET TRANSACTION ISOLATION LEVEL '
                . ($this->islv[$level] ?? 'REPEATABLE READ'));
        } catch (\PDOException $e) {
            $this->dbLog('setIsolationLevel', $e->getMessage());
        }
    }
    
    /**
     * トランザクションの開始
     * @return void
     */
    public function transaction(): void
    {
        try {
            $this->connectCheck();
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
     * @return void
     */
    public function commit(): void
    {
        try {
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
     * @return void
     */
    public function rollback(): void
    {
        try {
            if ($this->transaction_flag) {
                $this->transaction_flag = false;
                $this->connect->rollBack();
            }
        } catch (\PDOException $e) {
            $this->dbLog('rollback', $e->getMessage());
        }
    }
    
    /**
     * ステートメントの準備
     * @param string $statement_id プリペアドステートメントID
     * @param string $sql クエリ
     * @return object|bool
     */
    public function prepare(
        string $statement_id = 'stmt',
        string $sql = ''
    ) {
        try {
            $this->connectCheck();
            if (!isset($this->do[$statement_id])) {
                $this->do[$statement_id] = 'prepare';
            }
            if ($sql) {
                $this->sql = $sql;
            }
            $this->stmt[$statement_id] = $this->connect->prepare($this->sql);

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
     * @return void
     */
    public function nameFlag(
        bool $name_flag,
        string $statement_id = 'stmt'
    ): void {
        $this->name[$statement_id] = $name_flag;
    }

    /**
     * ステートメントの実行
     * @param array $params 挿入する値
     * @param string $statement_id プリペアドステートメントID
     * @return int 成功すれば変更した件数を返す
     */
    public function bind(
        array $params = [],
        string $statement_id = 'stmt'
    ): int {
        try {
            if (!isset($this->name[$statement_id])) {
                $this->name[$statement_id] = true;
            }

            $this->bind_params = [];
            $i = 1;
            foreach ($params as $k => $v) {
                if ($k === 0 and $this->do[$statement_id] === 'update') {
                    // array_spliceで入れた0の配列キーをupdate_timeに変える
                    $k = 'update_time';
                }
                $name = $this->name[$statement_id] ? $k : $i;

                $this->bind_params[$name] = $v;
                $this->bindDebug($name, $v);

                $this->stmt[$statement_id]->bindValue($name, $v,
                    is_numeric($v) ? \PDO::PARAM_INT : \PDO::PARAM_STR);
                $i ++;
            }
            $this->executeDebug($statement_id);
            $this->stmt[$statement_id]->execute();
            $count = $this->stmt[$statement_id]->rowCount();
            $this->executeDebugCount($count);
            return $count;
        } catch (\PDOException $e) {
            $this->dbLog('bind', $e->getMessage());
        }
    }
    
    /**
     * バインドのデバッグ表示
     * @param string|int $name
     * @param string|int|null $value
     * @return void
     */
    protected function bindDebug($name, $value): void {}
    
    /**
     * 処理実行のデバッグ表示
     * @param string $statement_id
     * @return void
     */
    protected function executeDebug(string $statement_id): void {}
    
    /**
     * 処理実行の時間と件数
     * @param int $count
     * @return void
     */
    protected function executeDebugCount(int $count): void {}
    
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
        try {
            $rows = [];
            $count = $this->bind($param, $statement_id);
            if ($count) {
                $this->stmt[$statement_id]->setFetchMode(\PDO::FETCH_ASSOC);
                $rows = $this->stmt[$statement_id]->fetchAll();
                if ($rows === false) {
                    throw new \Error('Fetch Error');
                }
            }
            return $rows;
        } catch (\PDOException $e) {
            $this->dbLog('bindFetchAll', $e->getMessage());
        }
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
        try {
            $obj = [];
            $count = $this->bind($param, $statement_id);
            if ($count) {
                $this->stmt[$statement_id]->setFetchMode(
                    \PDO::FETCH_CLASS, '\stdClass');
                $obj = $this->stmt[$statement_id]->fetchAll();
                if ($obj === false) {
                    throw new \Error('Fetch Error');
                }
            }
            return $obj;
        } catch (\PDOException $e) {
            $this->dbLog('bindFetchAll', $e->getMessage());
        }
    }
    
    /**
     * 1行フェッチ
     * @param string $statement_id プリペアドステートメントID
     * @return array|bool
     */
    public function fetch(string $statement_id = 'stmt')
    {
        try {
            $rows = $this->stmt[$statement_id]->fetch(\PDO::FETCH_ASSOC);
            return $rows;
        } catch (\PDOException $e) {
            $this->dbLog('fetch', $e->getMessage());
        }
    }
    
    /**
     * 1行フェッチクラス
     * @param string $class_name クラスオブジェクトとして取得する場合指定する
     * @param string $statement_id プリペアドステートメントID
     * @return object|bool
     */
    public function fetchClass(
        string $class_name,
        string $statement_id = 'stmt'
    ) {
        try {
            $rows = $this->stmt[$statement_id]->fetchObject($class_name);
            return $rows;
        } catch (\PDOException $e) {
            $this->dbLog('fetch', $e->getMessage());
        }
    }
    
    /**
     * プリペアドステートメントの解放
     * @param string $statement_id プリペアドステートメントID
     * @return void
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
        } catch (\PDOException $e) {
            $this->dbLog('stmtClose', $e->getMessage());
        }
    }
}
