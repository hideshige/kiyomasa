<?php
/**
 * データベース接続モジュール
 *
 * @author   Hideshige Sawada
 * @version  1.0.0.0
 * @package  device
 *
 */

namespace Bunroku\Kiyomasa\Device;

use PDO;
use PDOException;

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
                "SET sql_mode = 'STRICT_TRANS_TABLES, NO_ZERO_IN_DATE, "
                . "NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO'"
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
     * 実行時間の測定開始
     */
    protected function before()
    {
        $this->time = microtime(true);
    }

    /**
     * 実行時間の取得
     * @return float 実行時間
     */
    protected function after()
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
    protected function dbLog($error) {
        $error_mes = sprintf(
            "%s\n%s\n%s",
            $error,
            $this->sql,
            implode(',', $this->bind_params)
        );
        if ($this->debug) {
            //デバッグ表示
            \dump($error_mes);
        }
        throw new FwException($error_mes);
    }
}
