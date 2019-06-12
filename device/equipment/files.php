<?php
/**
 * ファイル モジュール
 *
 * @author   Sawada Hideshige
 * @version  1.0.0.1
 * @package  device/equipment
 * 
 */

namespace Php\Framework\Device\Equipment;

use Php\Framework\Device as D;

class Files
{
    /**
     * ファイルの移動
     * @param string $upload_file アップロードファイルのパス
     * @param string $save_file 保存先のディレクトリとファイル名
     * @param bool $scan_flag ウィルススキャンするかどうか
     * @return void
     * @throws \Error
     */
    public static function moveFile(
        string $upload_file,
        string $save_file,
        bool $scan_flag = false
    ): void {
        if ($scan_flag) {
            self::virusScan($upload_file);
        }
        if (!move_uploaded_file($upload_file, $save_file)) {
            throw new \Error('FILE MOVE ERROR');
        }
    }

    /**
     * ウィルススキャン
     * @param string $file ウィルススキャンするファイル
     * @return void
     */
    public static function virusScan(string $file): void
    {
        //ウィルスチェック（ウィルスだった場合ファイルを削除する）
        $do = sprintf('clamdscan "%s" --remove --log=%slogs/antivirus_%s.log',
            $file, SERVER_PATH, date('Ymd'));
        $debug = '';
        exec($do, $debug);
        chmod(sprintf(
            '%slogs/antivirus_%s.log', SERVER_PATH, date('Ymd')), 0644);
    }
    
    /**
     * MIME-TYPEの確認
     * @param string $file
     * @return string
     */
    public static function checkMime(string $file): string
    {
        $mime = '';
        if (file_exists($file)) {
            if (extension_loaded('fileinfo')) {
                $data = file_get_contents($file);
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_buffer($finfo, $data);
                finfo_close($finfo);
            } else {
                $mime = preg_replace('/(; | )[^ ]*/', '',
                    trim(shell_exec('file -bi ' . escapeshellcmd($file))));
            }
        }
        return $mime;
    }

    /**
     * サイズの確認
     * @param string $file ファイルデータのパス
     * @param int $limit 制限サイズ
     * @return void
     * @throws D\UserEx
     */
    public static function sizeCheck(string $file, int $limit): void
    {
        $file_byte = filesize($file);
        if ($file_byte > $limit) {
            throw new D\UserEx('ファイルサイズが大きすぎます');
        }
    }
}
