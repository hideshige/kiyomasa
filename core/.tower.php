<?php
/**
 * タワー　オートロード、エラーハンドラなど土台強化部
 *
 * @author   Sawada Hideshige
 * @version  1.0.2.0
 * @package  core
 * 
 */

namespace Php\Framework\Core;

use Php\Framework\Device\{ErrorInfo, UserEx};

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
            $arr[$k] = strtolower(preg_replace('/^_/', '',
                // スタッドリーキャップス記法をアンダースコア記法に変換
                preg_replace('/([A-Z])/', '_$1', $v)));
        }
        $file_name = SERVER_PATH . implode('/', $arr) . '.php';
        if (!file_exists($file_name)) {
            if (MODE >= MODE_DEBUG or ENV <= ENV_DEV) {
                trace();
            }
            throw new \Error('Class File Not Found: ' . $file_name);
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
        // ユーザエラーはユーザ用の例外へ
        if ($no === E_USER_ERROR or
            $no === E_USER_WARNING or $no === E_USER_NOTICE) {
            throw new UserEx($message, 10);
        }
        
        // 開発環境以外とライブラリ内のエラーは無視する
        if ((ENV > ENV_DEV and MODE === MODE_NORMAL)
            or strpos($file, '.library') !== false) {
            return;
        }
        
        switch ($no) {
            case E_ERROR: $type = 'エラー'; break;
            case E_WARNING: $type = '警告'; break;
            case E_NOTICE: $type = '注意'; break;
            case E_PARSE: $type = '構文不正'; break;
            case E_DEPRECATED: $type = '非推奨'; break;
            default: $type = '番号' . $no; break;
        }
        
        $info = new ErrorInfo;
        $info->set($type . ': ' . $message, $file, $line);
        
        // 警告と注意と非推奨以外は例外処理
        if ($no !== E_NOTICE and $no !== E_DEPRECATED and $no !== E_WARNING) {
            throw new \Error('エラーハンドラからエラーをスローします', 10);
        }
    }
);
