<?php
/**
 * 画像 モジュール
 *
 * @author   Sawada Hideshige
 * @version  1.1.4.2
 * @package  device
 * 
 */

namespace Php\Framework\Device;

class Image
{
    /**
     * サムネイル作成
     * @param string $file ファイルデータのパス
     * @param string $folder_name 保存先のパス
     * @param bool $scan_flag ウィルススキャンするかどうか
     * @param int $width_max 大画像の最大幅（これを超えるとリサイズする）
     * @param int $height_max 大画像の最大高さ
     * @param int $comp 画像の圧縮率(画像がPNGの場合には無視する)
     * @param string $file_type ファイルの型
     * @param string $set_name 保存したい名前。名前を自動生成する場合は空欄
     * @param string|int $file_no ファイルの識別番号
     * @param array $p 画像をリサイズした場合の背景色RGB
     * @param int $limit 制限サイズ
     * @return string 保存したファイル名
     * @throws UserException
     * @throws \Error
     */
    public static function thumbnail(
        string $file,
        string $folder_name,
        bool $scan_flag = true,
        int $width_max = 160,
        int $height_max = 160,
        int $comp = 98,
        string $file_type = '',
        string $set_name = '',
        $file_no = 0,
        array $p = ['255', '255', '255'],
        int $limit = 104857600
    ): string {
        //ウィルススキャン
//        if ($scan_flag) {
//            self::virusScan($file);
//        }

        //サイズとファイル形式のチェック
        self::sizeCheck($file, $limit);
        $itype = self::fileType($file, $file_type);

        switch ($itype) {
            case 'gif': $img = imagecreatefromgif($file); break;
            case 'jpg': $img = imagecreatefromjpeg($file); break;
            case 'png': $img = imagecreatefrompng($file); $comp = 9; break;
            default: throw new UserException('使えるのはGIF,JPEG,PNGのみです');
        }

        //画像の向きを修正
        $exif = @exif_read_data($file);
        if (isset ($exif['Orientation'])) {
            switch ($exif['Orientation']) {
                case 3: $img = imagerotate($img, 180, 0); break;
                case 6: $img = imagerotate($img, 270, 0); break;
                case 8: $img = imagerotate($img, 90, 0); break;
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
        $paint = imagefill($img2, 0, 0, $paint_color);
        if ($width > $width_max or $height > $height_max) {
            $img_res = imagecopyresampled($img2, $img, 0, 0, 0, 0, $width_max, $height_max, $width, $height);
        } else {
            $img_res = imagecopyresampled($img2, $img, 0, 0, 0, 0, $width, $height, $width, $height);
        }

        if (!$img_res) {
            throw new \Error('thumbnail resample error');
        }

        $name = $set_name ? ($file_type ? $set_name . '.' . $itype : $set_name)
            : md5(TIMESTAMP . S::$user['user_id']) . $file_no . '.' . $itype;
        $save_file = SERVER_PATH . 'public_html/img/' . $folder_name . $name;

        switch ($itype) {
            case 'gif': $save = imagegif($img2, $save_file, $comp); break;
            case 'jpg': $save = imagejpeg($img2, $save_file, $comp); break;
            case 'png': $save = imagepng($img2, $save_file, $comp); break;
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
     * イメージの保存
     * @param string $file ファイルデータのパス
     * @param string $save_folder_file 保存先のフォルダとファイル名
     * @param string $file_type ファイルの形式
     * @param int $limit 制限サイズ
     * @param bool $scan_flag ウィルススキャンするかどうか
     * @throws \Error
     */
    public static function upFile(
        string $file,
        string $save_folder_file,
        string $file_type = '',
        int $limit = 104857600,
        bool $scan_flag = true
    ): void {
        //if ($scan_flag) {
        //    self::virusScan($file);
        //}
        self::sizeCheck($file, $limit);
        $itype = self::fileType($file, $file_type);
        if (!$file_type) {
            $save_folder_file .= '.' .  $itype;
        }
        $res = move_uploaded_file($file, $save_folder_file);
        if (!$res) {
            throw new \Error('file move error');
        }
    }

    /**
     * ウィルススキャン
     * @param string $file ウィルススキャンするファイル
     */
    public static function virusScan(string $file): void
    {
        //ウィルスチェック（ウィルスだった場合ファイルを削除する）
        $do = sprintf(
            'clamdscan "%s" --remove --log=%slogs/antivirus_%s.log',
            $file,
            SERVER_PATH,
            date('Ymd')
        );
        $debug = '';
        exec($do, $debug);
        chmod(
            sprintf('%slogs/antivirus_%s.log', SERVER_PATH, date('Ymd')),
            0644
        );
    }

    /**
     * ファイルタイプの確認
     * @param string $file ファイルデータのパス
     * @param string $file_type ファイルの形式
     * @return string
     */
    private static function fileType(string $file, string $file_type): string
    {
        if (!$file_type) {
            //MIMEタイプを調べる
            $data = file_get_contents($file);
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $file_type = finfo_buffer($finfo, $data);
            finfo_close($finfo);
        }
        //画像のタイプにより処理が異なるので切り分ける
        if (preg_match('/(gif|GIF)/', $file_type)) {
            $itype = 'gif';
        } else if (preg_match('/(jp|JP)/', $file_type)) {
            $itype = 'jpg';
        } else if (preg_match('/(png|PNG)/', $file_type)) {
            $itype = 'png';
        } else {
            throw new UserException('画像タイプ不正 '
                . $file . ' ' . $file_type);
        }
        return $itype;
    }

    /**
     * サイズの確認
     * @param string $file ファイルデータのパス
     * @param int $limit 制限サイズ
     * @throws UserException
     */
    private static function sizeCheck(string $file, int $limit): void
    {
        $file_byte = filesize($file);
        if ($file_byte > $limit) {
            throw new UserException('ファイルサイズが大きすぎます');
        }
    }
}
