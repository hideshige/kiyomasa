<?php
/**
 * データベース（接続、クエリ関連）
 *
 * @author   Sawada Hideshige
 * @version  1.0.6.2
 * @package  device/db
 *
 */

namespace Php\Framework\Device\Db;

use Php\Framework\Device\Log;

class DbModule
{
    use DbCrud;
    use DbStatement;
    use DbDebug;
    
    private $db_server; // DBサーバ
    private $db_user; // DBユーザ
    private $db_password; // DBパスワード
    private $db_name; // DB名
    private $db_driver; // 使用するドライバ
    private $connect_flag = false; // データベースに接続されているかどうか
    private $connect; // データベースオブジェクト
    private $sql = ''; // 実行するSQL
    private $transaction_flag = false; // トランザクション実行中の場合TRUE
    private $lock_flag = false; // テーブル排他ロック中の場合TRUE
    
    /**
     * パラメータのセット
     * @param string $db_server
     * @param string $db_user
     * @param string $db_password
     * @param string $db_name
     * @param string $db_driver
     * @param bool $debug
     */
    public function __construct(
        string $db_server, 
        string $db_user, 
        string $db_password, 
        string $db_name, 
        string $db_driver,
        bool $debug
    ) {
        $this->db_server = $db_server;
        $this->db_user = $db_user;
        $this->db_password = $db_password;
        $this->db_name = $db_name;
        $this->db_driver = $db_driver;
        $this->debug = $debug;
    }
    
    /**
     * 接続
     * @return bool 成否
     * @throws \PDOException
     */
    public function connect(): bool {
        try {
            $res = true;
            $dsn = sprintf('%s:host=%s;dbname=%s;charset=utf8mb4',
                $this->db_driver, $this->db_server, $this->db_name);
            $this->connect = new \PDO($dsn, $this->db_user, $this->db_password,
                [\PDO::ATTR_PERSISTENT => false,
                \PDO::ATTR_EMULATE_PREPARES => false,
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
            $this->connect_flag = true;
            
            //$this->query("SET sql_mode = 'STRICT_TRANS_TABLES, NO_ZERO_IN_DATE'");
        } catch (\PDOException $e) {
            Log::error($e->getMessage());
            $res = false;
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
     * 実行
     * @global int $g_counter
     * @param string $sql 実行するSQL文
     * @param string $dev_sql 画面表示用・ログ用SQL文(バイナリをテキストに置き換えたもの)
     * @param string $statement_id ステートメントID
     * @return object|bool
     */
    public function query(
        string $sql,
        string $dev_sql = '',
        string $statement_id = 'stmt'
    ) {
        try {
            $this->connectCheck();
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

            $this->queryDebug($statement_id, $qt);
            return $this->stmt[$statement_id];
        } catch (\PDOException $e) {
            $this->dbLog('query', $e->getMessage());
        }
    }
    
    /**
     * テーブル排他ロック
     * （ロック中のテーブルは別の人は更新できない）
     * @params string $tables ロックするテーブル（カンマ区切り）
     * @return void
     */
    public function lock(string $tables): void
    {
        $this->connectCheck();
        
        // トランザクション使用中は実行できない。
        if ($this->transaction_flag) {
            throw new \Error('LOCK ERROR');
        }

        $this->lock_flag = true;
        $this->query(sprintf('LOCK TABLES %s WRITE',
            preg_replace('/,/', ' WRITE, ', $tables)));
    }

    /**
     * テーブル排他ロック解除
     * @return void
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
     * @param string $param パラメータ
     * @param string $statement_id ステートメントID
     * @return object|bool
     */
    public function call(
        string $name,
        string $param,
        string $statement_id = 'stmt'
    ) {
        try {
            $this->connectCheck();
            $res = $this->query(sprintf('CALL %s(%s)', $name, $param),
                '', $statement_id);
            return $res;
        } catch (\PDOException $e) {
            $this->dbLog('call', $e->getMessage());
        }
    }
}
