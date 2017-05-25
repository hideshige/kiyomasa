<?php
/**
 * タワー　例外処理やショートカットなど土台強化部
 *
 * @author   Sawada Hideshige
 * @version  1.0.0.0
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
    public function set($message, $file, $line)
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
    * @param string $class_name クラス名
    */
    function ($class_name)
    {
        $arr = explode('\\', $class_name);
        if (!isset($arr[1])) {
            throw new \Error('Class Name Error: ' . $class_name);
        }
        $count = count($arr);
        $under = preg_replace(
            '/^_/',
            '',
            // スタッドリーキャップス記法をアンダースコア記法に変換
            preg_replace('/([A-Z])/', '_$1', $arr[$count - 1])
        );
        $file_name = SERVER_PATH
            . strtolower($arr[$count - 2] . '/' . $under) . '.php';
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
     * @param integer $no
     * @param string $message
     * @param string $file
     * @param integer $line
     */
    function ($no, $message, $file, $line)
    {
        switch ($no) {
            case E_ERROR: $type = 'エラー'; break;
            case E_WARNING : $type = 'ワーニング'; break;
            case E_PARSE: $type = 'パースエラー'; break;
            case E_NOTICE: $type = '警告'; break;
            case E_DEPRECATED: $type = '非推奨'; break;
            default: $type = 'エラー番号 ' . $no; break;
        }
        
        if (!preg_match('/\.library/', $file)) {
            $info = new ErrorInfo;
            $info->set($type . ': ' . $message, $file, $line);
        }
        
        if ($no !== E_NOTICE and $no !== E_DEPRECATED) {
            throw new \Error('エラーハンドラからエラーをスローします', 10);
        }
    }
);
