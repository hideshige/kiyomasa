<?php
/**
 * 画像 モジュール
 *
 * @author   Sawada Hideshige
 * @version  1.1.5.5
 * @package  device/equipment
 * 
 */

namespace Php\Framework\Device\Equipment;

use Php\Framework\Device as D;

class Image extends Files
{
    private static $width; // 元画像の幅
    private static $height; // 元画像の高さ
    private static $new_width; // 書き出し画像の幅（余白部分も含む）
    private static $new_height; // 書き出し画像の高さ（余白部分も含む）
    
    /**
     * サムネイル作成
     * @param string $original 元ファイルデータのパス
     * @param string $file_name 保存先のパスと名前
     * @param string $file_type ファイルの型(jpg, gif, png)
     * @param int $width_max 大画像の最大幅（これを超えるとリサイズする）
     * @param int $height_max 大画像の最大高さ
     * @param int $comp 画像の圧縮率(画像がPNGの場合には無視される)
     * @param bool $space 余白を入れる場合TRUE,余白を切り抜く場合FALSE
     * @param array $p 余白の背景色RGB
     * @return void
     * @throws \Error
     */
    public static function thumbnail(
        string $original,
        string $file_name,
        string $file_type = '',
        int $width_max = 160,
        int $height_max = 160,
        int $comp = 98,
        bool $space = false,
        array $p = [255, 255, 255]
    ): void {
        if (!extension_loaded('gd')) {
            throw new \Error('GD NOT INSTALLED');
        }
        
        $img = self::createImage($original, $file_type);
        
        self::$width = imagesx($img);
        self::$height = imagesy($img);
        self::$new_width = $width_max;
        self::$new_height = $height_max;
        
        $size = self::imageSize($space);
        $img2 = self::resizeImage();
        
        if ($space or $file_type === 'gif') {
            // 塗りつぶし
            self::paintImage($img2, $p);
        } else {
            // 透過
            imagealphablending($img2, false);
            imagesavealpha($img2, true);
        }
        
        self::resampleImage($img, $img2, $size);
        self::saveImage($img2, $file_name, $file_type, $comp);

        imagedestroy($img);
        imagedestroy($img2);
        chmod($file_name, 0644);
    }
    
    /**
     * 型チェックと画像の作成
     * @param string $original 元ファイルデータのパス
     * @param string $file_type ファイルの型(jpg, gif, png) 参照渡し
     * @return void
     * @throws D\UserEx
     */
    private static function createImage(
        string $original,
        string &$file_type
    ) {
        if ($file_type === '') {
            $file_type = self::checkMime($original);
        }

        switch ($file_type) {
            case 'gif': $img = imagecreatefromgif($original); break;
            case 'jpg': $img = imagecreatefromjpeg($original); break;
            case 'png': $img = imagecreatefrompng($original); break;
            default:
                throw new D\UserEx(
                    'サムネイルを作成できるのはJPEG,GIF,PNGのみです');
        }
        
        if (extension_loaded('exif') and $file_type === 'jpg') {
            $img = self::rotate($img, $original);
        }
        return $img;
    }
    
    /**
     * 画像の向きを修正
     * @param resource $img
     * @param string $original 元ファイルデータのパス
     * @return resource
     * @throws \Error
     */
    private static function rotate($img, string $original)
    {
        $exif = exif_read_data($original);
        if (isset($exif['Orientation'])) {
            switch ($exif['Orientation']) {
                case 3: $img = imagerotate($img, 180, 0); break;
                case 6: $img = imagerotate($img, 270, 0); break;
                case 8: $img = imagerotate($img, 90, 0); break;
            }
        }
        if ($img === false) {
            throw new \Error('IMAGE ROTATE ERROR');
        }
        return $img;
    }
    
    /**
     * 指定のサイズにリサイズ
     * @return resource
     * @throws \Error
     */
    private static function resizeImage() {
        $img = imagecreatetruecolor(self::$new_width, self::$new_height);
        if ($img === false) {
            throw new \Error('IMAGE CREATE ERROR');
        }
        return $img;
    }
    
    /**
     * 書き出し画像サイズの中の絵の部分の幅と高さを決める
     * @param bool $space 余白を入れる場合TRUE,余白を切り抜く場合FALSE
     * @return array
     */
    private static function imageSize(bool $space): array
    {
        $width = self::$new_width;
        $height = self::$new_height;
        if (self::$width > $width or self::$height > $height) {
            if ($height / $width > self::$height / self::$width) {
                $percent = $width / self::$width;
                $height = round(self::$height * $percent, 0);
            } else {
                $percent = $height / self::$height;
                $width = round(self::$width * $percent, 0);
            }
        } else  {
            $width = self::$width;
            $height = self::$height;
        }
        if ($space === false) {
            // 書き出しサイズを変更する
            self::$new_width = $width;
            self::$new_height = $height;
        }
        return ['width' => $width, 'height' => $height];
    }
    
    /**
     * 背景色を塗りつぶす
     * @param resource $img
     * @param array $p 余白の背景色RGB
     * @throws \Error
     */
    private static function paintImage($img, array $p): void
    {
        $paint_color = imagecolorallocate($img, $p[0], $p[1], $p[2]);
        if (!imagefill($img, 0, 0, $paint_color)) {
            throw new \Error('THUMBNAIL PAINT ERROR');
        }
    }
    
    /**
     * 画像ファイルに絵の部分を張り付ける
     * @param resource $img
     * @param resource $img2
     * @param array $size 幅と高さの入った配列
     * @return void
     * @throws \Error
     */
    private static function resampleImage($img, $img2, array $size): void {
        $dst_x = round((self::$new_width - $size['width']) / 2);
        $dst_y = round((self::$new_height - $size['height']) / 2);
        
        $res = imagecopyresampled($img2, $img, $dst_x, $dst_y, 0, 0,
            $size['width'], $size['height'], self::$width, self::$height);

        if ($res === false) {
            throw new \Error('THUMBNAIL RESAMPLE ERROR');
        }
    }
    
    /**
     * 画像の保存
     * @param resource $img
     * @param string $file_name 保存先のパスと名前
     * @param string $file_type ファイルの型(jpg, gif, png)
     * @param int $comp 画像の圧縮率(画像がPNGの場合には無視される)
     * @throws \Error
     */
    private static function saveImage(
        $img,
        string $file_name,
        string $file_type,
        int $comp
    ): void {
        switch ($file_type) {
            case 'gif': $save = imagegif($img, $file_name, $comp); break;
            case 'jpg': $save = imagejpeg($img, $file_name, $comp); break;
            case 'png': $save = imagepng($img, $file_name, 9); break;
            default: $save = false;
        }

        if ($save === false) {
            throw new \Error('THUMBNAIL SAVE ERROR');
        }
    }
}
