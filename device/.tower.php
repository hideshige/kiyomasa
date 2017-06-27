<?php
/**
 * タワー　例外処理やショートカットなど土台強化部
 *
 * @author   Sawada Hideshige
 * @version  1.0.1.1
 * @package  device
 * 
 */

namespace Php\Framework\Device;

/**
 * ユーザー操作による例外
 */
class UserException extends \Exception
{
}

class ErrorInfo
{
    /**
     * エラー情報のセット
     * @global string $dump
     * @param string $message
     * @param string $file
     * @param int $line
     * @return void
     */
    public function set(string $message, string $file, int $line): void
    {
        S::$dbm->rollback();
        S::$dbm->unlock();

        // ログに記録し、開発環境の場合デバッグを表示
        $short_file = str_replace(SERVER_PATH, '', $file);
        $error = sprintf('%s(%d) %s', $short_file, $line, $message);
        Log::error($error);

        global $dump;
        $dump .= sprintf("# %s {{DUMP_LINE}}%d\n{{ERROR_INFO}}%s\n",
            $short_file, $line, $message);
    }
}

/**
 * パラメータのショートカット用スタティックオブジェクト
 *
 */
class S
{
    static $post; // 整形後のPOSTパラメータ
    static $get; // 整形後のGETパラメータ
    static $url; // URLパラメータ
    static $dbm; // DBマスターモジュール
    static $dbs; // DBスレーブモジュール
    static $mem; // memcachedモジュール
    static $disp; // テンプレートデータ
    static $user; // セッション上のユーザーデータ
    static $ouser; // ページに表示するユーザーデータ
    static $jflag; // そのモデルがJSON形式かHTML形式か
}

// オートロード
spl_autoload_register(
    /**
    * クラスファイルの読み込み
    * @param string $class クラス名
    */
    function (string $class): void
    {
        $class_name = str_replace(
            [NAME_SPACE . '\\', 'Php\Framework\\'], '', $class);
        $arr = explode('\\', $class_name);
        if (!isset($arr[1])) {
            throw new \Error('Class Name Error: ' . $class_name);
        }
        foreach ($arr as $k => $v) {
            $arr[$k] = strtolower(
                preg_replace('/^_/', '',
                    // スタッドリーキャップス記法をアンダースコア記法に変換
                    preg_replace('/([A-Z])/', '_$1', $v))
            );
        }
        $file_name = SERVER_PATH . implode('/', $arr) . '.php';
        if (!file_exists($file_name)) {
            throw new \Error(
                'Class File Not Found: ' . $file_name
            );
        }
        // リクワイア実行
        require $file_name;
    }
);

// エラーハンドラ
set_error_handler(
    /**
     * エラー処理
     * @param int $no
     * @param string $message
     * @param string $file
     * @param int $line
     * @return void
     * @throws \Error
     */
    function (int $no, string $message, string $file, int $line): void
    {
        switch ($no) {
            case E_ERROR: $type = 'エラー'; break;
            case E_WARNING : $type = 'ワーニング'; break;
            case E_PARSE: $type = 'パースエラー'; break;
            case E_NOTICE: $type = '警告'; break;
            case E_DEPRECATED: $type = '非推奨'; break;
            default: $type = 'エラー番号 ' . $no; break;
        }
        
        if (ENV <= 1 and !preg_match('/\.library/', $file)) {
            $info = new ErrorInfo;
            $info->set($type . ': ' . $message, $file, $line);
        }
        
        if (ENV <= 1 and $no !== E_NOTICE and $no !== E_DEPRECATED) {
            throw new \Error('エラーハンドラからエラーをスローします', 10);
        }
    }
);
