<?php
/**
 * 画像 モジュール
 *
 * @author   Sawada Hideshige
 * @version  1.1.5.0
 * @package  device/equipment
 * 
 */

namespace Php\Framework\Device\Equipment;

use Php\Framework\Device as D;

class Image extends Files
{
    /**
     * サムネイル作成
     * @param string $file ファイルデータのパス
     * @param string $folder_name 保存先のパス
     * @param int $width_max 大画像の最大幅（これを超えるとリサイズする）
     * @param int $height_max 大画像の最大高さ
     * @param int $comp 画像の圧縮率(画像がPNGの場合には無視する)
     * @param string $file_type ファイルの型
     * @param string $set_name 保存したい名前。名前を自動生成する場合は空欄
     * @param string|int $file_no ファイルの識別番号
     * @param array $p 画像をリサイズした場合の背景色RGB
     * @param int $limit 制限サイズ
     * @return string 保存したファイル名
     * @throws D\UserException
     * @throws \Error
     */
    public static function thumbnail(
        string $file,
        string $folder_name,
        int $width_max = 160,
        int $height_max = 160,
        int $comp = 98,
        string $file_type = '',
        string $set_name = '',
        $file_no = 0,
        array $p = ['255', '255', '255'],
        int $limit = 104857600
    ): string {
        //サイズとファイル形式のチェック
        self::sizeCheck($file, $limit);
        $itype = self::checkImageType($file, $file_type);

        switch ($itype) {
            case 'gif':
                $img = imagecreatefromgif($file);
                break;
            case 'jpg':
                $img = imagecreatefromjpeg($file);
                break;
            case 'png':
                $img = imagecreatefrompng($file);
                $comp = 9;
                break;
            default:
                throw new D\UserException('使えるのはGIF,JPEG,PNGのみです');
        }

        //画像の向きを修正
        $exif = @exif_read_data($file);
        if (isset($exif['Orientation'])) {
            switch ($exif['Orientation']) {
                case 3:
                    $img = imagerotate($img, 180, 0);
                    break;
                case 6:
                    $img = imagerotate($img, 270, 0);
                    break;
                case 8:
                    $img = imagerotate($img, 90, 0);
                    break;
            }
        }

        //指定のサイズにリサイズ
        $width = imagesx($img);
        $height = imagesy($img);
        if ($width > $width_max or $height > $height_max) {
            $check = $height_max / $width_max;
            $check2 = $height / $width;
            if ($check > $check2) {
                $percent = $width_max / $width;
                $height_max = round($height * $percent, 0);
            } else {
                $percent = $height_max / $height;
                $width_max = round($width * $percent, 0);
            }
            $img2 = imagecreatetruecolor($width_max, $height_max);
        } else {
            $img2 = imagecreatetruecolor($width, $height);
        }

        $paint_color = imagecolorallocate($img2, $p[0], $p[1], $p[2]);
        //$paint = imagefill($img2, 0, 0, $paint_color);
        if ($width > $width_max or $height > $height_max) {
            $img_res = imagecopyresampled($img2, $img, 0, 0, 0, 0,
                $width_max, $height_max, $width, $height);
        } else {
            $img_res = imagecopyresampled($img2, $img, 0, 0, 0, 0,
                $width, $height, $width, $height);
        }

        if (!$img_res) {
            throw new \Error('thumbnail resample error');
        }

        $name = $set_name ? ($file_type ? $set_name . '.' . $itype : $set_name)
            : md5(TIMESTAMP . S::$user['user_id']) . $file_no . '.' . $itype;
        $save_file = SERVER_PATH . $folder_name . $name;

        switch ($itype) {
            case 'gif':
                $save = imagegif($img2, $save_file, $comp);
                break;
            case 'jpg':
                $save = imagejpeg($img2, $save_file, $comp);
                break;
            case 'png':
                $save = imagepng($img2, $save_file, $comp);
                break;
        }

        if (!$save) {
            throw new \Error('thumbnail save error');
        }

        imagedestroy($img);
        imagedestroy($img2);
        chmod($save_file, 0644);
        return $name;
    }
    
    /**
     * ファイルタイプの確認
     * @param string $file ファイルデータのパス
     * @param string $file_type ファイルの形式
     * @return string
     * @throws D\UserException
     */
    private static function checkImageType(
        string $file,
        string $file_type
    ): string {
        if (!$file_type) {
            $file_type = self::checkMime($file);
        }
        //画像のタイプにより処理が異なるので切り分ける
        if (preg_match('/(gif|GIF)/', $file_type)) {
            $itype = 'gif';
        } else if (preg_match('/(jp|JP)/', $file_type)) {
            $itype = 'jpg';
        } else if (preg_match('/(png|PNG)/', $file_type)) {
            $itype = 'png';
        } else {
            throw new D\UserException('画像タイプ不正 '
                . $file . ' ' . $file_type);
        }
        return $itype;
    }
}
